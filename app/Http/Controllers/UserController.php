<?php


namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:15|min:3',
            'email' => 'email'
        ]);

        $data = $request->only('name', 'email');

        User::find(Auth::id())->update($data);
        return response()->json([
            'status' => true,
            'message' => trans('messages.user_updated')
        ], 200);
    }
}
