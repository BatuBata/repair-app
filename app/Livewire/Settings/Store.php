<?php

namespace App\Livewire\Settings;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class Store extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $warranty_terms = '';

    public string $default_warranty_days = '30';

    public ?TemporaryUploadedFile $logo = null;

    #[Locked]
    public ?string $logo_path = null;

    public string $status = '';

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function mount(): void
    {
        User::activeAccount(admin: true);
        $setting = StoreSetting::find(1);
        if ($setting) {
            $this->fill($setting->only(['name', 'address', 'phone', 'default_warranty_days', 'logo_path']));
            $this->warranty_terms = $setting->warranty_terms ?? '';
            $this->default_warranty_days = (string) $setting->default_warranty_days;
        }
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        $data = $this->validate([
            'name' => ['string', 'max:255'],
            'address' => ['string', 'max:65535'],
            'phone' => ['string', 'max:15'],
            'warranty_terms' => ['nullable', 'string', 'max:65535'],
            'default_warranty_days' => ['required', 'integer', 'between:0,4294967295'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ], [
            'name.string' => 'Nama toko harus berupa teks.',
            'name.max' => 'Nama toko maksimal 255 karakter.',
            'address.string' => 'Alamat harus berupa teks.',
            'address.max' => 'Alamat maksimal 65.535 karakter.',
            'phone.string' => 'Telepon harus berupa teks.',
            'phone.max' => 'Telepon maksimal 15 karakter.',
            'warranty_terms.string' => 'Syarat garansi harus berupa teks.',
            'warranty_terms.max' => 'Syarat garansi maksimal 65.535 karakter.',
            'default_warranty_days.required' => 'Default hari garansi wajib diisi.',
            'default_warranty_days.integer' => 'Default hari garansi harus berupa bilangan bulat.',
            'default_warranty_days.between' => 'Default hari garansi harus antara 0 dan 4.294.967.295.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat JPG, PNG, GIF, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ]);
        unset($data['logo']);

        $newLogoPath = null;
        if ($this->logo) {
            $newLogoPath = $this->logo->store('logos', 'public');
            if (! $newLogoPath) {
                throw new \RuntimeException('Logo gagal disimpan.');
            }
        }

        try {
            DB::transaction(function () use ($data, $newLogoPath) {
                User::activeAccount(admin: true);
                StoreSetting::updateOrCreate(['id' => 1], [...$data, ...($newLogoPath ? ['logo_path' => $newLogoPath] : [])]);
            });
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }
            throw $exception;
        }

        $this->logo_path = $newLogoPath ?? $this->logo_path;
        $this->reset('logo');
        $this->default_warranty_days = (string) $data['default_warranty_days'];
        $this->status = 'Pengaturan toko berhasil disimpan.';
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.settings.store', ['logoUrl' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null])
            ->layout('layouts.app', ['title' => 'Pengaturan toko']);
    }
}
