<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\StaffManagement;
use App\Models\UserBlock;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\UserActivity;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'loginEmployer']]);
    }


    public function login(Request $request)
    {
        DB::enableQueryLog();
        app('log')->debug($request->all());
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid Credential', 'code' => '400'], 401);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            $id = User::select('id')->where('email', $request->email)->pluck('id');
            
            return response()->json(['message' => 'ID Pengguna atau Kata Laluan Tidak Sah', "code" => 401], 401);

        }

        //$useradmin = User::select('role')->where('email', $request->email)->pluck('role');
        $id = User::select('id')->where('email', $request->email)->pluck('id');

        $userStatus = DB::table('staff_management as s')
            ->select('s.status')
            ->where('s.email', $request->email)->first();

        if ($userStatus->status != 1) {
            return response()->json(['message' => 'User is Inactive', "code" => 202], 202);
        }
        $screenroute = DB::table('screen_access_roles')
        ->select(DB::raw('screens.screen_route'))
        ->join('screens', function ($join) {
            $join->on('screens.id', '=', 'screen_access_roles.screen_id');
        })
        ->where('screen_access_roles.staff_id', '=', $id)
        ->where('screen_access_roles.status', '=', '1')
        ->get();

    $screenroutealt = DB::table('screen_access_roles')
        ->select(DB::raw('screens.screen_route_alt'))
        ->join('screens', function ($join) {
            $join->on('screens.id', '=', 'screen_access_roles.screen_id');
        })
        ->where('screen_access_roles.staff_id', '=', $id)
        ->where('screen_access_roles.status', '=', '1')
        ->get();
                if (!empty($screenroute[0])) {
                    $tmp = json_decode(json_encode($screenroute[0]), true)['screen_route'];
                    $tmp_alt = json_decode(json_encode($screenroutealt[0]), true)['screen_route_alt'];
                    return $this->createNewToken($token, $tmp, $tmp_alt);
                } else {
                    $tmp = "";
                    return response()->json(['message' => 'User has not right to access any form. Please contact to Admin', 'code' => '201'], 201);
                }
            }
        /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out'], 200);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token, $tmp, $tmp_alt)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 14400,
            'user' => auth()->user(),
            'route' => $tmp,
            'route_alt' => $tmp_alt,
            'code' => '200'
        ]);
    }
            
        
    




}