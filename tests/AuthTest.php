<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AuthTest extends TestCase
{
    public function testOtpCreate()
    {
        $verification = factory(\App\Models\PhoneNumberVerification::class)->make();
        if ($verification->otp && $verification->expire_at - \Carbon\Carbon::now()->timestamp <= 120);
            $this->assertResponseOk();
    }
}
