<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Libs\GoogleAuthenticator;
use App\Events\SendEmailEvent;
use App\Mail\ActivateAccountMail;
use App\Constants\ErrorCode;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $v = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password'  => 'required|min:3|confirmed',
        ]);

        if ($v->fails())
        {
            return response()->json([
                'status' => 0,
                'error' => 'registration_validation_error',
                'errors' => $v->errors()
            ], 422);
        }

        $confirmation_code = str_random(30);

        $user = new User;
        $user->email = $request->email;
        $user->username = $request->username;
        $user->password = bcrypt($request->password);
        // $user->activated = false;
        $user->activated = true;
        $user->confirmation_code = $confirmation_code;
        $user->save();

        event(new SendEmailEvent($user, new ActivateAccountMail($user)));

        return response()->json(['status' => 1], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            $user = Auth::user();
            $user->auth_token = $token;
            $user->save();
            
            $data = array();
            $data['id'] = $user->id;
            $data['status'] = 1;
            $data['activated'] = $user->activated;

            if (!$user->activated) {
                return response()->json($data, 200);
            }
            
            $data['email'] = $user->email;
            $data['username'] = $user->username;
            $data['role'] = $user->role;
            $data['activated'] = $user->activated;
            $data['g2f_enabled'] = $user->g2f_enabled;
            $data['kyc_verified'] = $user->kyc_verified;

            if ($user->g2f_enabled) {
                return response()->json($data, 200);
            } else {
                $data['auth_token'] = $token;
                return response()->json($data, 200)->header('Authorization', $token);
            }
        }

        return response()->json(['status' => 0, 'error' => 'login_error'], 401);
    }

    public function logout()
    {
        $this->guard()->logout();
        $user = Auth::user();

        return response()->json([
            'status' => 1,
            'msg' => 'Logged out Successfully.'
        ], 200);
    }

    public function validateG2fLogin(Request $request) {
        $input = $request->input();
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            $gAuth = new GoogleAuthenticator();
            $user = User::where('email', $input['email'])->first();

            if ($gAuth->checkCode($user->g2f_key, $input['g2f_code'])) {
                $user->auth_token = $token;
                $user->save();

                return response()->json([
                    'status' => 1,
                    'auth_token' => $token
                ], 200)->header('Authorization', $token);
            } else {
                return response()->json([
                    'status' => 0,
                    // 'code' => $gAuth->getCode($user->g2f_key),
                    'error_code' => ErrorCode::GOOGLE_OTP_INVALID,
                    'msg' => 'The G2F code is incorrect.'
                ]);
            }
        }

        return response()->json(['status' => 0, 'error' => 'login_error'], 401);
    }

    public function user(Request $request)
    {
        $user = User::find(Auth::user()->id);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function refresh()
    {
        if ($token = $this->guard()->refresh()) {
            return response()
                ->json(['status' => 'successs'], 200)
                ->header('Authorization', $token);
        }

        return response()->json(['error' => 'refresh_token_error'], 401);
    }

    private function guard()
    {
        return Auth::guard();
    }
}

