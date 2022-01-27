<?php


namespace App\Http\Controllers;

use App\Models\PhoneNumberVerification;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Components\OTP;

/**
 * Class AuthController
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * @var int
     */
    protected $sensorExpirationTime = 600;
    /**
     * @var int
     */
    protected $mobileExpirationTime = 120;

    /**
     * AuthController constructor.
     */
    public function __construct()
    {
        $this->middleware('refresh', [
           'only' => [
               'refreshToken'
           ]
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function otpCreate(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required',
            'country_id' => 'required|numeric',
            'device_type' => 'in:sensor,mobile'
        ]);

        if ($request->get('device_type') == 'sensor')
        {
            $is_sensor = true;
            $expiration = $this->sensorExpirationTime;
            if (!DeviceController::updateDeviceStatus($request->get('phone'), 1))
                 return response()->json([
                     'status' => false,
                     'message' => trans('messages.device_not_found')
                 ], 404);
        }
        else
        {
            $is_sensor = false;
            $expiration = $this->mobileExpirationTime;
        }

        if ($this->numberIsBlocked($request->get('phone'), $is_sensor))
            return response()->json([
                'status' => false,
                'message' => trans('messages.number_is_blocked')
            ], 403);

        return OTP::create($request->only('country_id', 'phone'), $expiration, $is_sensor)
            ? response()->json([
                'status' => true,
                'message' => trans('messages.sent')
            ], 200)
            : response()->json([
                'status' => false,
                'message' => trans('messages.not_sent')
            ], 404);
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     * @throws ValidationException
     */
    public function otpValidate(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required',
            'country_id' => 'required',
            'otp' => 'required|digits:6'
        ]);

        try
        {
            if($otp = PhoneNumberVerification::where('phone', $request->get('phone'))
                ->where('otp', $request->get('otp'))
                ->where('expire_date', '>', Carbon::now()->timestamp)
                ->where('country_id', $request->get('country_id'))
                ->first())
            {
                DB::beginTransaction();
                $user = User::firstOrCreate(
                    [
                       'phone' => $request->get('phone'),
                       'country_id' => $request->get('country_id')
                    ],
                    [
                       'verified_at' => Carbon::now(),
                       'status' => 1
                    ]
                );
                $otp->delete();
                DB::commit();

                $token = JWTAuth::fromUser($user);
                return $this->respondWithToken($token, $user);
            }
        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'otp.not_found'
            ], 404);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'otp.not_validated'
        ], 404);

    }

    /**
     * @param $phone
     * @param $is_sensor
     * @return bool
     */
    public function numberIsBlocked($phone, $is_sensor)
    {
        if ($is_sensor)
            return false;

        $verification = PhoneNumberVerification::where('phone', $phone)->first();
        if ($verification)
            if (Carbon::now()->timestamp - date_timestamp_get($verification->updated_at) > 86400)
                return false;
            else if ($verification->resend_count >= 3)
                return true;

        return false;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function refreshToken(Request $request)
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }
}
