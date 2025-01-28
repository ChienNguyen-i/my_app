<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\EmailVerified;
use App\Mail\PasswordChanged;
use App\Mail\ResetPassword;
use App\Mail\VerifyEmail;
use App\Mail\Welcome;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    protected $ttl_default = 60 * 60;
    protected $ttl_30days = 60 * 60 * 24 * 30;

    public function register(AuthRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->trashed()) {
                $user->restore();
                return $this->responsesService->success(200, __('messages.restore_successful'), ['user' => $user]);
            }
            return $this->responsesService->error(400, __('messages.email_unique'));
        }
        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $data['image'] = $this->manageFilesService->uploadFile($request, 'image', 'users/images');
        $user = User::create($data);
        if (!$user) {
            return $this->responsesService->error(400, __('messages.add_failed'));
        }
        DB::table('users')->where('id', $user->id)->update(['created_by' => $user->id, 'updated_by' => $user->id]);
        $token = Str::random(64);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('password_reset_tokens')->insert(['email' => $request->email, 'token' => $token, 'created_at' => Carbon::now()]);
        try {
            $mailData = ['user' => $user, 'token' => $token];
            Mail::to($request->email)->queue(new Welcome($mailData));
            Mail::to($request->email)->queue(new VerifyEmail($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('messages.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(
            200,
            __('messages.add_successful_with_verification_link'),
            ['user' => $user, 'token' => $token]
        );
    }

    public function login(AuthRequest $request)
    {
        $remember = $request->boolean('remember');
        $token = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
        if (!$token) {
            return $this->responsesService->error(401, __('messages.unauthorized'));
        }
        $ttl = $remember ? $this->ttl_30days : $this->ttl_default;
        Auth::factory()->setTTL($ttl);
        return $this->responsesService->success(200, __('messages.login_successful'), ['token' => $token, 'expires_in' => $ttl])->cookie('ttl', $ttl, $ttl);
    }

    public function profile()
    {
        $user = Auth::user();
        if (!$user) {
            return $this->responsesService->error(401, __('messages.unauthorized'));
        }
        return $this->responsesService->success(200, __('messages.success'), $user);
    }

    public function logout()
    {
        Auth::logout();
        return $this->responsesService->success(200, __('messages.logout_successful'));
    }

    public function refreshToken()
    {
        if (!Auth::check()) {
            return $this->responsesService->error(401, __('messages.unauthorized'));
        }
        $ttl_cookie = request()->cookie('ttl', $this->ttl_default);
        $ttl = ($ttl_cookie == $this->ttl_30days) ? $this->ttl_30days : $this->ttl_default;
        $token = Auth::refresh();
        return $this->responsesService->success(
            200,
            __('messages.token_refresh_successful'),
            ['token' => $token, 'expires_in' => $ttl]
        );
    }

    public function verifyEmail(AuthRequest $request)
    {
        $passwordResetToken = DB::table('password_reset_tokens')->where('email', $request->email)->where('token', $request->token)->first();
        if (!$passwordResetToken) {
            return $this->responsesService->error(400, __('messages.invalid_token'));
        }
        DB::table('password_reset_tokens')->where('email', $request->email)->where('token', $request->token)->delete();
        $updated = DB::table('users')->where('email', $request->email)->update(['email_verified_at' => Carbon::now()]);
        if (!$updated) {
            return $this->responsesService->error(500, __('messages.email_verification_failed'));
        }
        $user = DB::table('users')->where('email', $request->email)->first();
        try {
            $mailData = ['user' => $user];
            Mail::to($request->email)->queue(new EmailVerified($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('messages.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(200, __('messages.email_verified'));
    }

    public function forgotPassword(AuthRequest $request)
    {
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        $token = Str::random(64);
        $password_reset = DB::table('password_reset_tokens')->insert(['email' => $request->email, 'token' => $token, 'created_at' => Carbon::now()]);
        if (!$password_reset) {
            return $this->responsesService->error(400, __('messages.failed_try'));
        }
        $user = DB::table('users')->where('email', $request->email)->first();
        try {
            $mailData = ['user' => $user, 'token' => $token];
            Mail::to($request->email)->queue(new ResetPassword($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('messages.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(200, __('messages.password_reset_link'), $token);
    }

    public function resetPassword(AuthRequest $request)
    {
        $passwordResetToken = DB::table('password_reset_tokens')->where('email', $request->email)->where('token', $request->token)->first();
        if (!$passwordResetToken) {
            return $this->responsesService->error(400, __('messages.invalid_token'));
        }
        $createdAt = Carbon::parse($passwordResetToken->created_at);
        if ($createdAt->addHour()->isPast()) {
            return $this->responsesService->error(400, __('messages.token_expired'));
        }
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('users')->where('email', $request->email)->update(['password' => Hash::make($request->password)]);
        $user = DB::table('users')->where('email', $request->email)->first();
        try {
            $mailData = ['user' => $user];
            Mail::to($request->email)->queue(new PasswordChanged($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('messages.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(200, __('messages.password_reset'));
    }

    public function sendLinkPassword(AuthRequest $request)
    {
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        $token = Str::random(64);
        $password_reset = DB::table('password_reset_tokens')->insert(['email' => $request->email, 'token' => $token, 'created_at' => Carbon::now()]);
        if (!$password_reset) {
            return $this->responsesService->error(400, __('messages.failed_try'));
        }
        $user = DB::table('users')->where('email', $request->email)->first();
        if (!$user) {
            return $this->responsesService->error(404, __('messages.user_not_found'));
        }
        try {
            $mailData = ['user' => $user, 'token' => $token];
            Mail::to($request->email)->queue(new ResetPassword($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('messages.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(200, __('messages.password_reset_link'), $token);
    }

    public function sendLinkVerifyEmail(AuthRequest $request)
    {
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        $token = Str::random(64);
        $password_reset = DB::table('password_reset_tokens')->insert(['email' => $request->email, 'token' => $token, 'created_at' => Carbon::now()]);
        if (!$password_reset) {
            return $this->responsesService->error(400, __('messages.failed_try'));
        }
        $user = DB::table('users')->where('email', $request->email)->first();
        if (!$user) {
            return $this->responsesService->error(404, __('messages.user_not_found'));
        }
        try {
            $mailData = ['user' => $user, 'token' => $token];
            Mail::to($request->email)->queue(new VerifyEmail($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('messages.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(200, __('messages.email_verification_link'), $token);
    }

    public function updatePassword(AuthRequest $request)
    {
        if (!Hash::check($request->old_password, Auth::user()->password)) {
            return $this->responsesService->error(400, __('messages.old_password_not_match'));
        }
        if (Hash::check($request->new_password, Auth::user()->password)) {
            return $this->responsesService->error(400, __('messages.old_password_match_new_password'));
        }
        $user = Auth::user();
        $user->password = Hash::make($request->new_password);
        $user->updated_by = $user->id;
        if (!$user->save()) {
            return $this->responsesService->error(400, __('messages.password_change_failed'));
        }
        $user->tokens->each(function ($token) {
            $token->delete();
        });
        try {
            $mailData = ['user' => $user];
            Mail::to($user->email)->queue(new PasswordChanged($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('messages.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(200, __('messages.password_change_successful'));
    }
}
