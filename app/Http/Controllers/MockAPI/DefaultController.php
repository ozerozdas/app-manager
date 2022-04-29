<?php

namespace App\Http\Controllers\MockAPI;

use App\Http\Controllers\Controller;
use App\Services\GoogleAPI;
use Illuminate\Http\Request;

class DefaultController extends Controller {

    public function check(Request $request){
        $googleAPI = new GoogleAPI();
        $result = $googleAPI->checkReceipt($request->input('receipt'));
        return response()->json($result);
    }

    public function purchase(Request $request){
        $request->validate([
            'token' => 'required',
            'receipt' => 'required|integer',
        ]);
        $result = [
            'status' => false,
            'message' => 'Purchase failed'
        ];
        $googleAPI = new GoogleAPI();
        $purchaseResponse = $googleAPI->purchase($request->token, $request->receipt);
        if ($purchaseResponse) {
            $result['status'] = true;
            $result['message'] = 'Successfully purchased';
        }
        return response()->json($result);
    }

}
