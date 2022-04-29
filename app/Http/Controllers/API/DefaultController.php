<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use function response;

class DefaultController extends Controller {
    public function index() {
        return response()->json([
            'status' => true,
            'message' => 'API is working'
        ]);
    }

    public function register(Request $request) {
        $request->validate([
            'uid' => 'required|unique:device',
            'appId' => [
                'required',
                Rule::exists('app_info', 'id'),
            ],
            'language' => 'required',
            'operatingSystem' => 'required',
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

    public function purchase(Request $request) {
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
        $auth = null;
        if ($device->operating_system == 'android') {
            $auth = base64_encode($app_info->google_username . ':' . $app_info->google_password);
        }elseif ($device->operating_system == 'ios') {
            $auth = base64_encode($app_info->ios_username . ':' . $app_info->ios_password);
        }
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to purchase'
            ]);
        }

//        $response = Http::withHeaders([
//            'Accept' => 'application/json',
//            'Authorization' => 'Basic ' . $auth,
//        ])->timeout(2)->post('http://localhost:8000/mock/purchase', [
//            'token' => $request->token,
//            'receipt' => $request->receipt
//        ]);

//        dump($response);exit;
        return response()->json($result);
    }
}
