<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'sms_log';

    protected $guarded = [];

    public static function getSms($is_delivered)
    {
        return SmsLog::where('is_delivered', $is_delivered)->get();
    }
}
