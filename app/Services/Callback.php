<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class Callback {
    public function sendFeed($uid, $appId, $eventId) {
        $log = DB::table('event_log')->select(['device.id as device_id', 'device.app_id', 'event_log.id as event_id', 'device.callback'])
            ->join('device', function ($join) {
                $join->on('event_log.uid', '=', 'device.uid');
                $join->on('event_log.app_id', '=', 'device.app_id');
            })
            ->where([
                'event_log.uid' => $uid,
                'event_log.app_id' => $appId,
                'event_log.id' => $eventId,
            ])
            ->first();
        if ($log->callback) {
            $client = new Client();
            $response = $client->request('POST', $log->callback, [
                'multipart' => [
                    [
                        'name' => 'device_id',
                        'contents' => $log->device_id,
                    ],
                    [
                        'name' => 'app_id',
                        'contents' => $log->app_id,
                    ],
                    [
                        'name' => 'event_id',
                        'contents' => $log->event_id,
                    ],
                ]
            ]);
            $result = json_decode($response->getBody()->getContents());
            return !empty($result->status) ? $result->status : false;
        }
        return false;
    }
}
