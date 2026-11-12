<?php

namespace App\Livewire\Users;

use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use PasswordValidationRules;

    #[Locked]
    public ?int $userId = null;

    #[Locked]
    public string $mode = 'list';

    public string $name = '';

    public string $username = '';

    public string $role = 'teknisi';

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public string $status = '';

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function create(): void
    {
        User::activeAccount(admin: true);
        $this->reset();
        $this->resetValidation();
        $this->mode = 'create';
    }

    public function edit(mixed $id): void
    {
        User::activeAccount(admin: true);
        $user = $this->findUser($id);
        $this->reset();
        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->role = $user->role->value;
        $this->is_active = $user->is_active;
        $this->mode = 'edit';
    }

    public function resetPassword(mixed $id): void
    {
        User::activeAccount(admin: true);
        $user = $this->findUser($id);
        $this->reset();
        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->mode = 'reset';
    }

    public function cancel(): void
    {
        User::activeAccount(admin: true);
        $this->reset();
        $this->resetValidation();
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        abort_unless(in_array($this->mode, ['create', 'edit', 'reset'], true), 422);
        if ($this->mode === 'reset') {
            $this->validate(['password' => $this->passwordRules()]);
            $this->mutate(function () {
                $this->findUser($this->userId)->forceFill(['password' => $this->password, 'remember_token' => Str::random(60)])->save();
            });
        } else {
            $this->username = Str::lower(trim($this->username));
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:100', 'regex:/\A[a-z0-9._-]+\z/', Rule::unique('users')->ignore($this->userId)],
                'role' => ['required', Rule::enum(UserRole::class)],
                'is_active' => ['boolean'],
            ];
            if ($this->mode === 'create') {
                $rules['password'] = $this->passwordRules();
            }
            $data = $this->validate($rules);
            unset($data['password_confirmation']);
            $this->mutate(function () use ($data) {
                if ($this->mode === 'create') {
                    User::create($data);
                } else {
                    $user = $this->findUser($this->userId);
                    $this->protectLastAdmin($user, $data['role'], $data['is_active']);
                    $user->update($data);
                }
            });
        }
        $this->reset('password', 'password_confirmation');
        $this->mode = 'list';
        $this->status = 'Pengguna berhasil disimpan.';
    }

    public function toggleActive(mixed $id): void
    {
        User::activeAccount(admin: true);
        $this->mutate(function () use ($id) {
            $user = $this->findUser($id);
            $this->protectLastAdmin($user, $user->role->value, ! $user->is_active);
            $user->forceFill(['is_active' => ! $user->is_active, 'remember_token' => Str::random(60)])->save();
        });
        $this->status = 'Status pengguna berhasil diubah.';
    }

    protected function findUser(mixed $id): User
    {
        $validated = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1']])->validate();

        return User::query()->whereKey($validated['id'])->lockForUpdate()->firstOrFail();
    }

    protected function mutate(Closure $action): void
    {
        DB::transaction(function () use ($action) {
            // Semua mutasi akun mengunci baris tertua yang tidak dihapus; SQLite juga memperoleh kunci tulis sebelum membaca.
            DB::table('users')->where('id', User::min('id'))->update(['id' => DB::raw('id')]);
            User::activeAccount(admin: true);
            $action();
        }, 5);
    }

    protected function protectLastAdmin(User $user, string $role, bool $active): void
    {
        if ($user->role === UserRole::Admin && $user->is_active && ($role !== 'admin' || ! $active)
            && ! User::where('role', 'admin')->where('is_active', true)->whereKeyNot($user->id)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Minimal harus ada satu admin aktif.']);
        }
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.users.index', ['users' => User::orderBy('name')->get()])->layout('layouts.app', ['title' => 'Pengguna']);
    }
}
