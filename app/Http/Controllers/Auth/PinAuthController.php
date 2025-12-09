<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PinCodeMail;

class PinAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function sendPin(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->first();

    // Если пользователь не существует - создаем его
    if (!$user) {
        $user = User::create([
            'name' => explode('@', $request->email)[0], // Имя из email
            'email' => $request->email,
            'password' => null,
            'role' => 'user',
            'status' => 'active'
        ]);
    }

    // Генерируем PIN
    $pin = Str::random(6);
    $user->update([
        'pin_code' => $pin,
        'pin_expires_at' => now()->addMinutes(30)
    ]);

    // В development режиме показываем PIN вместо отправки email
    if (app()->environment('local')) {
        return redirect()->route('login.verify')
            ->with('email', $request->email)
            ->with('debug_pin', $pin);
    }

    // Отправляем PIN на почту
    Mail::to($user->email)->send(new PinCodeMail($pin));

    return redirect()->route('login.verify')->with('email', $request->email);
}

    public function showVerifyForm()
    {
        return view('auth.verify');
    }

    public function verifyPin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'pin_code' => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)
            ->where('pin_code', $request->pin_code)
            ->first();

        if ($user && $user->isPinValid()) {
            auth()->login($user);
            $user->update(['pin_code' => null, 'pin_expires_at' => null]);
            
            return redirect()->intended('/admin/dashboard');
        }

        return back()->withErrors(['pin_code' => 'Неверный PIN код или срок действия истек']);
    }

    public function logout()
    {
        auth()->logout();
        return redirect('/login');
    }
}