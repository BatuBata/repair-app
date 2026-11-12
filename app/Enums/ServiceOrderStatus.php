<?php

namespace App\Enums;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

enum ServiceOrderStatus: string
{
    case Diterima = 'diterima';
    case Dikerjakan = 'dikerjakan';
    case Selesai = 'selesai';
    case Diambil = 'diambil';
    case Batal = 'batal';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Diterima => in_array($next, [self::Dikerjakan, self::Batal], true),
            self::Dikerjakan => in_array($next, [self::Selesai, self::Batal], true),
            self::Selesai => $next === self::Diambil,
            self::Diambil, self::Batal => false,
        };
    }

    public function assertTransitionTo(self $next, UserRole $role): void
    {
        if ($role !== UserRole::Admin) {
            throw new AuthorizationException('Hanya admin dapat mengubah status.');
        }
        if (! $this->canTransitionTo($next)) {
            throw ValidationException::withMessages(['status' => 'Perubahan status tidak diizinkan.']);
        }
    }
}
