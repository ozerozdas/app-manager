<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AppleAPI {
    private $appId;
    private $username;
    private $password;

    /**
     * @throws \Exception
     */
    public function __construct($appId, $username, $password) {
        $this->appId = $appId;
        $this->username = $username;
        $this->password = $password;
        $this->checkUser();
    }

    public function checkUser() : bool {
        $appInfo = DB::table('app_info')->where([
            'id' => $this->appId
        ])->first();
        if (!($this->username == $appInfo->google_username && $this->password == $appInfo->google_password)) {
            throw new \Exception("Invalid username or password");
        }
        return true;
    }

    public function checkReceipt($receipt) : object {
        $lastChar = (integer) substr($receipt, -1);
        $expireDate = date_create('', timezone_open('-0600'));
        return (object) [
            'status' => $lastChar % 2 == 0,
            'expireDate' => $expireDate->format('Y-m-d H:i:s')
        ];
    }
    public function purchase($token, $receipt) : bool {
        return $token == $token && $this->checkReceipt($receipt)->status;
    }

    public function purchaseCheck($token, $receipt) : bool {
        return $token == $token && $this->checkReceipt($receipt)->status;
    }

    public function checkSubscription($receipt) : bool {
        $lastTwoChar = (integer) substr($receipt, -2);
        return ($lastTwoChar % 6) == 0;
    }
}
