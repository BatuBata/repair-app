<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            if (! User::where('role', UserRole::Admin)->exists()) {
                $password = config('repair.admin_initial_password');
                Validator::make(['password' => $password], [
                    'password' => ['required', 'string', Password::default()],
                ], ['password.required' => 'Isi ADMIN_INITIAL_PASSWORD secara lokal sebelum menjalankan seed.'])->validate();
                User::create([
                    'name' => 'Admin',
                    'username' => 'admin',
                    'password' => $password,
                    'role' => UserRole::Admin,
                    'is_active' => true,
                ]);
            }
            foreach (['Mesin cuci', 'TV', 'Laptop', 'Komputer', 'Monitor', 'Printer', 'Catokan', 'HP', 'Lainnya'] as $name) {
                Category::firstOrCreate(['name' => $name], ['is_active' => true]);
            }
            StoreSetting::firstOrCreate(['id' => 1], [
                'name' => '', 'address' => '', 'phone' => '', 'default_warranty_days' => 30,
            ]);
        });
    }
}
