<?php

namespace App\Services;

class GoogleAPI {
    public function checkReceipt($receipt) {
        $lastChar = (integer) substr($receipt, -1);
        $expireDate = date_create('', timezone_open('-0600'));
        return (object) [
            'status' => (bool)($lastChar % 2),
            'expireDate' => $expireDate->format('Y-m-d H:i:s')
        ];
    }
    public function purchase($token, $receipt) {
        return $token == $token && $this->checkReceipt($receipt)->status;
    }
}
