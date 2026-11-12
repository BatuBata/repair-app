<?php

namespace App\Providers;

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Translation\Translator;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(LogoutResponse::class, fn () => new class implements LogoutResponse
        {
            public function toResponse($request)
            {
                return redirect('/login');
            }
        });
    }

    public function boot(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('username', $request->input('username'))->first();

            return $user && Hash::check($request->input('password'), $user->password) && $user->is_active ? $user : null;
        });
        RateLimiter::for('login', function (Request $request) {
            $username = $request->input('username');
            $key = is_string($username) ? Str::lower($username) : '';

            return Limit::perMinute(5)->by(Str::transliterate($key.'|'.$request->ip()))->response(function (Request $request, array $headers) {
                $request->flashExcept('password', 'password_confirmation');
                $errors = (new ViewErrorBag)->put('default', new MessageBag([
                    'username' => 'Terlalu banyak percobaan masuk. Coba lagi dalam satu menit.',
                ]));

                return response()->view('pages::auth.login', compact('errors'), 429, $headers);
            });
        });
        Livewire::addPersistentMiddleware([EnsureActiveAccount::class, EnsureAdmin::class]);
        app(Translator::class)->addLines([
            'auth.failed' => 'Username atau password salah, atau akun tidak aktif.',
            'auth.throttle' => 'Terlalu banyak percobaan masuk. Coba lagi dalam :seconds detik.',
            'validation.required' => ':attribute wajib diisi.',
            'validation.string' => ':attribute harus berupa teks.',
            'validation.min.string' => ':attribute minimal :min karakter.',
            'validation.max.string' => ':attribute maksimal :max karakter.',
            'validation.confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'validation.current_password' => 'Password saat ini salah.',
            'validation.unique' => ':attribute sudah digunakan.',
            'validation.regex' => 'Format :attribute tidak valid.',
            'validation.enum' => 'Peran tidak valid.',
            'validation.password.letters' => 'Password harus mengandung huruf.',
            'validation.password.mixed' => 'Password harus mengandung huruf besar dan huruf kecil.',
            'validation.password.numbers' => 'Password harus mengandung angka.',
            'validation.password.symbols' => 'Password harus mengandung simbol.',
            'validation.password.uncompromised' => 'Password ini pernah bocor. Gunakan password lain.',
            'validation.boolean' => 'Status aktif tidak valid.',
            'validation.attributes.username' => 'Username',
            'validation.attributes.name' => 'Nama',
            'validation.attributes.password' => 'Password',
            'validation.attributes.current_password' => 'Password saat ini',
        ], 'id');
    }
}
