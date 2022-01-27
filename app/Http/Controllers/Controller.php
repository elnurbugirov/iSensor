<?php

namespace App\Http\Controllers;

use App\Components\SMS;
use App\Models\Device;
use App\Models\SmsLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    protected function respondWithToken($token, $user = null)
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => $user
        ];
    }

    public function getCountries()
    {
        return DB::select("SELECT id, iso, code FROM countries");
    }

    public function warning(Request $request)
    {
        if ($request->get('token') == env('NOTIFY_KEY'))
        {
            try {
                $user_device_conditions = Device::conditions($request->get('did'))[0];
                $range = json_decode($user_device_conditions->conditions)[0]->range;
                $device_id = $request->get('did');
                $user = DB::select(
                    "SELECT u.name, u.country_id, u.phone, ud.description, ud.device_id
                       FROM users as u
                       INNER JOIN user_devices as ud
                       ON u.id = ud.user_id
                       AND ud.device_id = '$device_id'"
                );
                $data = $user[0];
                $data->time = Carbon::now()->format('H:i');
                $data->date = Carbon::now()->format('Y-m-d');
                $data->description = $this->solveSpaces($data->description);
                $response = '';
                if ($request->has('t') and $request->has('h'))
                {
                    $response = 'temperatur:%20' . $request->get('t') . '%20(min:%20' . $range->temp->min . ',%20 max:%20' . $range->temp->max;
                    $response .= ',%20nemislik:%20' . $request->get('h') . '%20(min:%20' . $range->hum->min . ',%20 max:%20' . $range->hum->max;
                }
                else if ($request->has('t'))
                {
                    $response = 'temperatur:%20' . $request->get('t') . '%20(min:%20' . $range->temp->min . ',%20 max:%20' . $range->temp->max;
                }
                else if ($request->has('h'))
                {
                    $response = 'nemislik:%20' . $request->get('h') . '%20(min:%20' . $range->hum->min . ',%20 max:%20' . $range->hum->max;
                }

                $message = 'Hormetli%20' . $data->name . ',%20hazirda%20(' . $data->time . ',%20' . $data->date . ')%20' . $data->description . '%20unvaninda';
                $message .= '%20yerlesen %20qurguda%20 (ID:%20' . $data->device_id . ')%20' . $response;

                SMS::send($data->phone, $message, $data->country_id);
            }
            catch (\Exception $exception)
            {
                return 1;
            }

            return 2;
        }

        return 3;
    }

    public function solveSpaces($text)
    {
        $textArray = explode(' ', $text);
        return implode('%20', $textArray);
    }

    public function checkSmsDelivery()
    {
       $notDeliveredSms =  SmsLog::getSms(false);
       $checking = [];
      foreach ($notDeliveredSms as $sms)
      {
         if (SMS::check($sms->sms_id) == 400)
         {
             $sms = SmsLog::find($sms->id);
             $sms->is_delivered = true;
             $sms->update();
         }
      }

      return SmsLog::where('is_delivered', 1)->get();
    }
}
