<?php


namespace App\Components;

use \App\Models\PhoneNumberVerification ;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


/**
 * Class OTP
 * @package App\Components
 */
class OTP
{
    /**
     * @var int
     */
    protected $deviceExpirationTime = 600;
    /**
     * @var int
     */
    protected $phoneExpirationTime = 120;

    /**
     * @param $credentials
     * @param $expiration
     * @param null $is_sensor
     * @return bool
     */
    public static function create($credentials, $expiration, $is_sensor = null)
    {
        $otp = rand(100000, 999999);
        try
        {
            DB::beginTransaction();
            $verification = PhoneNumberVerification::updateOrCreate(
                [
                    'country_id' => $credentials['country_id'],
                    'phone' => $credentials['phone'],
                ],
                [
                    'otp' => $otp,
                    'expire_date' => Carbon::now()->timestamp + $expiration
                ]
            );

            if (!$verification->wasRecentlyCreated)
                $verification->increment('resend_count');

            if ($is_sensor)
                SMS::send($credentials['phone'], '##'.$otp.'##');
            else
                SMS::send($credentials['phone'], $otp);

            DB::commit();
        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            return false;
        }
        return true;
    }
}
