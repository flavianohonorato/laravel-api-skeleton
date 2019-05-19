<?php

namespace App\Http\Controllers;

use App\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $rules = [
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255|unique:users',
            'password'  => 'required|confirmed|min:6',
        ];

        $input = $request->only(
            'name', 'email', 'password', 'password_confirmation'
        );
        $validator = Validator::make($input, $rules);

        if($validator->fails()) {
            $error = $validator->messages()->toJson();
            return $this->errorResponse($error);
        }
        $name       = $request->name;
        $email      = $request->email;
        $password   = $request->password;
        $user       = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password)
        ]);
        $verification_code = str_random(30);

        DB::table('user_verifications')->insert([
            'user_id'   =>  $user->id,
            'token'     =>  $verification_code
        ]);
        $subject = "Por favor verifique seu endereço de email";

        Mail::send('emails.user-verificationß', [
            'name'              => $name,
            'verification_code' => $verification_code
        ],
        function($mail) use ($email, $name, $subject){
            $mail->from(getenv('FROM_EMAIL_ADDRESS'), config('app.name'));
            $mail->to($email, $name);
            $mail->subject($subject);
        });
        return $this->showMessage('Obrigado por inscrever-se! Verifique seu e-mail para completar seu cadastro.');
    }

    /**
     * @param $verification_code
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyUser($verification_code)
    {
        $check = DB::table('user_verifications')->where('token', $verification_code)->first();
        if(!is_null($check)){
            $user = User::find($check->user_id);
            if($user->is_verified == 1){
                return $this->showMessage('Sua conta já foi verificada antes');
            }
            $user->update(['is_verified' => 1]);
            DB::table('user_verifications')->where('token',$verification_code)->delete();
            return $this->showMessage('Você verificou com sucesso seu endereço de e-mail');
        }
        return $this->errorResponse("O código de verificação é inválido", 304);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $rules = [
            'email'     => 'required|email',
            'password'  => 'required',
        ];
        $input = $request->only('email', 'password');

        $validator = Validator::make($input, $rules);
        if($validator->fails()) {
            $error = $validator->messages()->toJson();
            return $this->errorResponse($error);
        }
        $credentials = [
            'email'         => $request->email,
            'password'      => $request->password,
            'is_verified'   => 1
        ];
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return $this->errorResponse("Credenciais inválidas. Certifique-se de inserir as informações certas e verificou seu endereço de e-mail", 401);
            }
        } catch (JWTException $e) {
            return $this->errorResponse("Não foi possível criar token", 500);
        }
        return $this->getOne(['token' => $token]);
    }

    /**
     * Invalidate the token, so user cannot use it anymore
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        $this->guard()->logout();
        return $this->showMessage("Logout");
    }

    /**
     * Return auth guard
     */
    private function guard()
    {
        return Auth::guard();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recover(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->errorResponse("Endereço de e-mail não encontrado.", 401);
        }
        try {
            Password::sendResetLink($request->only('email'), function (Message $message) {
                $message->subject('Seu link de redefinição de senha');
            });
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            return $this->errorResponse($error_message, 401);
        }
        return $this->showMessage("Um e-mail de recuperação de senha foi enviado! Por favor verifique seu email");
    }

    /**
     * Get authenticated user
     */
    public function user()
    {
        $user = User::find(auth()->id());
        return response()->json([
            'status' => 'success',
            'data'   => $user
        ]);
    }

}
