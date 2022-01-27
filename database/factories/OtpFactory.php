<?php

use App\Models\PhoneNumberVerification;

$factory->define(PhoneNumberVerification::class, function () {
    return [
        'otp' => rand(100000, 999999),
        'expire_at' => Carbon\Carbon::now()->timestamp + 120
    ];
});
