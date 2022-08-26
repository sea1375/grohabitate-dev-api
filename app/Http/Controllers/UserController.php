<?php

namespace App\Http\Controllers;

use App\User;
use App\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Libs\GoogleAuthenticator;
use App\Constants\ErrorCode;
use App\Events\SendEmailEvent;
use App\Mail\ForgotPasswordMail;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return response()->json(
            [
                'status' => 'success',
                'users' => $users->toArray()
            ], 200);
    }

    public function show(Request $request, $id)
    {
        $user = User::find($id);

        return response()->json(
            [
                'status' => 'success',
                'user' => $user->toArray()
            ], 200);
    }

    public function activateAccount($confirmation_code) {
        if (!$confirmation_code) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_CONFIRMATION_LINK,
                'msg' => 'Activation link is invalid.'
            ]);
        }

        $user = User::where('confirmation_code', $confirmation_code)->first();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_CONFIRMATION_LINK,
                'msg' => 'Activation link is invalid.'
            ]);
        }

        $user->confirmation_code = '';
        $user->activated = true;
        $user->save();

        return response()->json([
            'status' => 1,
            'email' => $user->email,
            'username' => $user->username,
            'activated' => $user->activated
        ], 200);
    }

    public function enableG2f() {
        $user = Auth::user();

        if (!$user || !$user->activated) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_REQUEST,
                'msg' => 'Invalid request'
            ]);
        }

        if ($user->g2f_enabled) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::G2FKEY_ALREADY_CREATED,
                'msg' => 'G2f Key is already created.'
            ]);
        }

        $gAuth = new GoogleAuthenticator();
        $code = $gAuth->generateSecret();

        $user->g2f_key = $code;
        $user->save();

        $qrUrl = "https://chart.googleapis.com/chart?chs=500x500&chld=M|0&cht=qr&chl=otpauth://totp/138.197.157.141:" . $user->email . "?secret=" . $code . "&issuer=138.197.157.141";
        
        return response()->json([
            'status' => 1,
            'g2f_key' => $code,
            'qr_url' => $qrUrl
        ]);
    }

    public function validateG2fCode(Request $request) {
        $user = Auth::user();
        $input = $request->input();
        $gAuth = new GoogleAuthenticator();

        if ($gAuth->checkCode($user->g2f_key, $input['g2f_code'])) {
            if (!$user->g2f_enabled) {
                $user->g2f_enabled = true;
                $user->save();
            }
            return response()->json([
                'status' => 1
            ], 200);
        }

        return response()->json([
            'status' => 0,
            'error_code' => ErrorCode::GOOGLE_OTP_INVALID,
            'msg' => 'The G2F code is incorrect.'
        ]);
    }

    public function changePassword(Request $request) {
        $user = Auth::user();
        $input = $request->input();
        $gAuth = new GoogleAuthenticator();

        if ($user->g2f_enabled)
        {
            if (!isset($input['g2f_code'])) {
                return response()->json([
                    'status' => 0,
                    'error_code' => ErrorCode::INVALID_REQUEST,
                    'msg' => 'Invalid request.'
                ]);
            }
            if (!$gAuth->checkCode($user->g2f_key, $input['g2f_code'])) {
                return response()->json([
                    'status' => 0,
                    'error_code' => ErrorCode::GOOGLE_OTP_INVALID,
                    'msg' => 'The G2F code is incorrect.'
                ]);
            }
        }

        if (!isset($input['current_password']) || !isset($input['new_password']) || !isset($input['password_confirmation'])) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_REQUEST,
                'msg' => 'Invalid request.'
            ]);
        }

        if (\Hash::check($input['current_password'], $user->password) == false) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::WRONG_PASSWORD,
                'msg' => 'Current password is wrong.'
            ]);
        }

        if ($input['new_password'] != $input['password_confirmation']) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::CONFIRM_PASSWORD_DISMATCHED,
                'msg' => 'New password and confirm_password not matched.'
            ]);
        }

        $user->password = bcrypt($input['new_password']);
        $user->save();

        return response()->json(['status' => 1], 200);
    }

    public function sendForgotEmail(Request $request) {
        $input = $request->input();
        if (!isset($input['email'])) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_REQUEST,
                'msg' => 'Invalid request.'
            ]);
        }

        $user = User::where('email', $input['email'])->first();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_REQUEST,
                'msg' => 'Invalid request'
            ]);
        }

        $resetPassword = ResetPassword::where('user_id', $user->id)->first();
        if (!$resetPassword) {
            $resetPassword = new ResetPassword();
            $resetPassword->user_id = $user->id;
        }

        $resetPassword->confirm_link = str_random(50);
        $resetPassword->expiration_time = Carbon::now()->addMinute(15);
        $resetPassword->save();

        event(new SendEmailEvent($user, new ForgotPasswordMail($user, $resetPassword->confirm_link)));

        return response()->json(['status' => 1], 200);
    }

    public function validateResetPasswordLink($confirmation_link) {
        if (!$confirmation_link) {
            return [
                'status' => 0,
                'error_code' => ErrorCode::INVALID_CONFIRMATION_LINK,
                'msg' => 'Confirmation link is invalid.'
            ];
        }

        $resetPassword = ResetPassword::where('confirm_link', $confirmation_link)->first();
        if (!$resetPassword) {
            return [
                'status' => 0,
                'error_code' => ErrorCode::INVALID_CONFIRMATION_LINK,
                'msg' => 'Confirmation link is invalid.'
            ];
        }

        $user = User::where('id', $resetPassword->user_id)->first();
        if (!$user) {
            return [
                'status' => 0,
                'error_code' => ErrorCode::INVALID_CONFIRMATION_LINK,
                'msg' => 'Confirmation link is invalid.'
            ];
        }

        $time = Carbon::now();
        if ($time > $resetPassword->expiration_time) {
            return [
                'status' => 0,
                'error_code' => ErrorCode::CONFIRMATION_LINK_EXPIRED,
                'msg' => 'Confirmation link is expired.'
            ];
        }

        return [
            'status' => 1,
            'confirmation_link' => $confirmation_link,
            'user' => $user,
            'reset_password' => $resetPassword
        ];
    }

    public function validateResetPasswordLinkRequest(Request $request) {
        $input = $request->input();
        if (!isset($input['confirmation_link'])) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_REQUEST,
                'msg' => 'Invalid request.'
            ]);
        }

        $confirmation_link = $input['confirmation_link'];
        $result = $this->validateResetPasswordLink($confirmation_link);

        if ($result['status'] == 1) {
            return response()->json([
                'status' => 1,
                'confirmation_link' => $confirmation_link
            ]);
        }
        return response()->json($result);
    }

    public function resetPassword(Request $request) {
        $input = $request->input();
        if (!isset($input['confirmation_link']) || !isset($input['password'])) {
            return response()->json([
                'status' => 0,
                'error_code' => ErrorCode::INVALID_REQUEST,
                'msg' => 'Invalid request.'
            ]);
        }

        $confirmation_link = $input['confirmation_link'];
        $password = $input['password'];
        $result = $this->validateResetPasswordLink($confirmation_link);

        if ($result['status'] == 0) {
            return response()->json($result);
        }

        $result['user']->password = bcrypt($password);
        $result['user']->save();
        $result['reset_password']->delete();

        return response()->json(['status' => 1], 200);
    }
}
