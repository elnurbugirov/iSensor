<?php


namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Device extends Model
{
    protected $table = 'user_devices';

    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public static function conditions($device_id)
    {
        return DB::select("SELECT JSON_EXTRACT(user_device_condition, '$.conditions') as conditions
                          FROM user_devices_conditions
                          WHERE device_id = $device_id")[0];
    }
}
