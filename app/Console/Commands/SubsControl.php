<?php

namespace App\Console\Commands;

use App\Services\AppleAPI;
use App\Services\Callback;
use App\Services\GoogleAPI;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

class SubsControl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:subs-control';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will check the subscriptions and if the subscription is expired it will delete.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        // Check the subscriptions and if the subscription is expired it will delete.
        DB::table('subscription')
            ->join('device', function ($join) {
                $join->on('subscription.uid', '=', 'device.uid');
                $join->on('subscription.app_id', '=', 'device.app_id');
            })
            ->join('app_info', 'app_info.id', '=', 'device.app_id')
            ->where(['status' => 1])
            ->get()
            ->each(function ($subscription) {
                if ($subscription->operating_system == 'android') {
                    $client = new GoogleAPI($subscription->app_id, $subscription->google_username, $subscription->google_password);
                    $status = $client->checkSubscription($subscription->receipt);
                }elseif ($subscription->operating_system == 'ios') {
                    $client = new AppleAPI($subscription->app_id, $subscription->ios_username, $subscription->ios_password);
                    $status = $client->checkSubscription($subscription->receipt);
                }
                $subs = DB::table('subscription')->where([
                    'uid' => $subscription->uid,
                    'app_id' => $subscription->app_id,
                    'status' => 1
                ])->update(['status' => $status]);

                if ($subs && $status != $subscription->status) {
                    $log = DB::table('event_log')->insertGetId([
                        'uid' => $subscription->uid,
                        'app_id' => $subscription->app_id,
                        'event_name' => 'canceled',
                        'event_type' => '0',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    if ($log) {
                        $callback = new Callback();
                        $callback->sendFeed($subscription->uid, $subscription->app_id, $log);
                    }
                }
            });
        $output = new ConsoleOutput();
        $output->writeln('Subscriptions are checked.');
        return 1;
    }
}
