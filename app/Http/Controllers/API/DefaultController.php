<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AppleAPI;
use App\Services\Callback;
use App\Services\GoogleAPI;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use function response;

class DefaultController extends Controller {
    public function register(Request $request) : \Illuminate\Http\JsonResponse {
        $request->validate([
            'uid' => 'required|unique:device',
            'appId' => [
                'required',
                Rule::exists('app_info', 'id'),
            ],
            'language' => 'required',
            'operatingSystem' => [
                'required',
                'in:android,ios',
            ],
        ]);
        $status = DB::table('device')->insert([
            'uid' => $request->uid,
            'app_id' => $request->appId,
            'language' => $request->language,
            'operating_system' => $request->operatingSystem,
            'client_token' => Uuid::uuid4()->toString(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Successfully registered' : 'Failed to register'
        ]);
    }

    /**
     * @throws \Exception
     */
    public function purchase(Request $request) : \Illuminate\Http\JsonResponse {
        $request->validate([
            'token' => 'required',
            'receipt' => 'required|integer',
        ]);
        $result = [
            'status' => false,
            'message' => 'Failed to purchase'
        ];

        $device = DB::table('device')->where('client_token', $request->token)->first();
        $app_info = DB::table('app_info')->where('id', $device->app_id)->first();

        $subscription = DB::table('subscription')->where([
            'uid' => $device->uid,
            'app_id' => $device->app_id,
        ])->first();
        if ($subscription) {
            $result['message'] = 'Already subscribed';
            return response()->json($result);
        }

        $response = $client = null;
        if ($device->operating_system == 'android') {
            $client = new GoogleAPI($app_info->id, $app_info->google_username, $app_info->google_password);
            $response = $client->purchase($request->token, $request->receipt);
        }elseif ($device->operating_system == 'ios') {
            $client = new AppleAPI($app_info->id, $app_info->ios_username, $app_info->ios_password);
            $response = $client->purchase($request->token, $request->receipt);
        }

        if ($response and $client) {
            $purchaseCheck = $client->purchaseCheck($request->token, $request->receipt);
            if ($purchaseCheck) {
                $result = DB::table('subscription')->insert([
                    'uid' => $device->uid,
                    'app_id' => $device->app_id,
                    'receipt' => $request->receipt,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                if ($result) {
                    $log = DB::table('event_log')->insertGetId([
                        'uid' => $device->uid,
                        'app_id' => $device->app_id,
                        'event_name' => 'started',
                        'event_type' => '1',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    if ($log) {
                        $callback = new Callback();
                        $callback->sendFeed($device->uid, $device->app_id, $log);
                    }
                }
                $result = [
                    'status' => $result,
                    'message' => $result ? 'Successfully purchased' : 'Failed to purchase'
                ];
            }
        }

        return response()->json($result);
    }

    public function checkSubscription(Request $request) : \Illuminate\Http\JsonResponse {
        $request->validate([
            'token' => 'required',
        ]);
        $device = DB::table('device')->where('client_token', $request->token)->first();
        $subscription = DB::table('subscription')->where([
            'uid' => $device->uid,
            'app_id' => $device->app_id,
        ])->first();
        $status = !empty($subscription->status) && (bool)$subscription->status;
        return response()->json([
            'status' => $status,
            'message' => $status ? 'Subscribed' : 'Not subscribed'
        ]);
    }

    public function basicReport() : array {
        return DB::select("select
                el.app_id, date(el.created_at) as date, sum(if(el.event_type = 1, 1, 0)) as started, sum(if(el.event_type = 0, 1, 0)) as canceled
            from device d
            inner join event_log el on el.uid = d.uid and el.app_id = d.app_id
            group by date(el.created_at), el.app_id
            order by date asc");
    }
}
