<?php


namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Device;
use App\Components\OTP;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    protected $sensorExpirationTime = 600;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $data = [];
        $devices = User::getDevices();
        foreach ($devices as $device)
        {
            $user_device_conditions = Device::conditions($device->device_id);
            $range = json_decode($user_device_conditions->conditions)[0]->range;
            $data[] = [
                'device' => $device,
                'data' => Data::where('devices_id', $device->device_id)->first(),
                'range' => $range
            ];

            if ($device->verified != 2
                and (Carbon::now()->timestamp - date_timestamp_get($device->updated_at)) > $this->sensorExpirationTime)
            {
                $device->verified = 3;
                $device->save();
            }
        }

        return $data;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'device_id' => 'required|numeric|exists:devices,device_id|unique:user_devices,device_id',
            'country_id' => 'required|numeric|exists:countries,id',
            'phone' => 'required',
            'description' => 'required'
        ]);
        $data = [
            'user_id' => Auth::id(),
            'device_id' => $request->get('device_id'),
            'country_id' => $request->get('country_id'),
            'phone' => $request->get('phone'),
            'verified' => 1,
            'description' => $request->get('description')
        ];
        try
        {
            DB::beginTransaction();
            $device = new Device();
            $device->fill($data);
            $device->save();
            OTP::create($device->toArray(), $this->sensorExpirationTime, true);
            DB::commit();
        }
        catch (\Exception $exception)
        {
            DB::rollBack();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Device saved successfully',
        ], 200);
    }

    public function delete($id)
    {
        try
        {
            Device::findOrFail($id)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'device is deleted successfully'
            ], 200);
        }
        catch (ModelNotFoundException $exception)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'device not found'
            ], 404);
        }
        catch (\Exception $exception)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'device is not deleted'
            ], 404);
        }
    }
    public function setRange(Request $request, $id)
    {
        try {
            $device = Device::find($id);
            $parameter = $request->get('parameter');
            $range = [$request->get('min'), $request->get('max')];

            DB::beginTransaction();
            $saved = true;
            $result = DB::select("SELECT user_device_condition
            FROM user_devices_conditions
            WHERE device_id = '$device->device_id'")[0]->user_device_condition;

            $params = json_decode($result, true);
            $params['conditions'][0]['range'][$parameter]['min'] = $range[0];
            $params['conditions'][0]['range'][$parameter]['max'] = $range[1];

            $conditions = json_encode($params, true);

            DB::update("UPDATE user_devices_conditions
            set user_device_condition = '$conditions'
            WHERE device_id = '$device->device_id'");

            DB::commit();
        }
        catch (\Exception $exception)
        {
            $saved = false;
            DB::rollBack();
        }

        return $saved
            ? response()->json([
                'status' => true,
                'message' => 'the range set successfully'
            ], 200)
            : response()->json([
                'status' => false,
                'message' => 'the range cannot set successfully'
            ], 404);
    }

    /**
     * @param $device_phone
     * @param $status
     * @return bool
     */
    public static function updateDeviceStatus($device_phone, $status)
    {
        try
        {
            $t = Device::where('phone', $device_phone)
                ->firstOrFail()
                ->update(['verified' => $status]);
        }
        catch (\Exception $exception)
        {
            return false;
        }

        return true;
    }
}
