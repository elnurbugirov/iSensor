<?php


namespace App\Components;


use App\Models\SmsLog;

class SMS
{
    /**
     * @param $phone
     * @param $text
     * @param int $country_id
     * @return bool
     */
    public static function send($phone, $text, $country_id = 15)
    {
        $az = ($country_id == 15) ? true : false;

        $apiUrl = ($az) ?
            'http://www.poctgoyercini.com/api_http/sendsms.asp' :
            'http://y.postaguvercini.com/api_http/sendsms.asp';
        $params = ($az) ?
            'user=isensor.az_service & password=012345 & gsm=' . $phone . ' & text='.$text :
            'user=isensor.az & password=012345 & gsm=' . $phone . ' & text='.$text;

        $iso88591_2 = mb_convert_encoding($params, 'ISO-8859-1', 'UTF-8');
        $sms_response = null;
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $iso88591_2);
            curl_setopt($curl, CURLOPT_URL, $apiUrl);
            parse_str(curl_exec($curl), $sms_response);
            curl_close($curl);

            $sms_response['phone'] = $phone;
            $sms_response['country_id'] = $country_id;
            $sms_response['message'] = $text;

            $log = new SmsLog();
            $log->fill([
                'country_id' => $country_id,
                'phone' => $phone,
                'sms_id' => $sms_response['message_id'],
                'content' => $text,
                'status' => $sms_response['errno']
            ])->save();
        } catch (\Exception $e) {

        }

        return $sms_response
            ? true
            : false;
    }

    public static function check($sms_id)
    {
        $apiUrl = 'http://www.poctgoyercini.com/api_http/querysms.asp';
        $params = 'user=isensor.az_service & password=012345 & message_id='.$sms_id ;
        $iso88591_2 = mb_convert_encoding($params, 'ISO-8859-1', 'UTF-8');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $iso88591_2);
        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        parse_str(curl_exec($curl), $sms_response);
        curl_close($curl);

        return $sms_response['status'];
    }
}
