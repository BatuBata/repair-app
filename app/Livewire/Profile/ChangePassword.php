<?php

namespace App\Livewire\Profile;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class ChangePassword extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $status = '';

    public function boot(): void
    {
        User::activeAccount();
    }

    public function updatePassword(): void
    {
        $user = User::activeAccount();
        $this->validate(['current_password' => $this->currentPasswordRules(), 'password' => $this->passwordRules()]);
        $user->forceFill(['password' => $this->password, 'remember_token' => Str::random(60)])->save();
        session()->regenerate();
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->status = 'Password berhasil diubah.';
    }

    public function render(): View
    {
        $user = User::activeAccount();

        return view('livewire.profile.change-password', compact('user'))->layout('layouts.app', ['title' => 'Profil']);
    }
}
