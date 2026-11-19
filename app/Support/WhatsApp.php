<?php

namespace App\Support;

final class WhatsApp
{
    public static function url(string $phone, string $message): string
    {
        return 'https://wa.me/'.PhoneNumber::normalize($phone).'?text='.rawurlencode($message);
    }

    public static function receiptMessage(string $name, string $category, ?string $description, string $number, string $link, string $store): string
    {
        $item = trim($category.' '.$description);

        return "Halo {$name}, barang {$item} sudah kami terima dengan nomor {$number}. Cek status: {$link} — {$store}";
    }

    public static function inspectionMessage(string $name, string $number, string $notes, string $link, string $store): string
    {
        return "Halo {$name}, berikut hasil pengecekan servis {$number}.\n\nHasil pengecekan & rencana tindakan:\n{$notes}\n\nMohon konfirmasi apakah perbaikan dapat dilanjutkan. Status: {$link} — {$store}";
    }

    public static function invoiceMessage(string $name, string $number, int $total, ?string $warrantyUntil, string $link, string $store, bool $unrepairable = false): string
    {
        $amount = 'Rp'.number_format($total, 0, ',', '.').($total === 0 ? ' (Gratis)' : '');

        $warranty = $warrantyUntil ? "Garansi s.d. {$warrantyUntil}" : 'Tanpa garansi';

        $status = $unrepairable ? 'tidak dapat dilanjutkan karena barang tidak dapat diperbaiki' : 'sudah selesai';

        return "Halo {$name}, servis {$number} {$status}. Total {$amount}. {$warranty}. Nota: {$link} — {$store}";
    }

    public static function warrantyMessage(string $name, string $number, string $item, string $warrantyUntil, string $store): string
    {
        return "Halo {$name}, garansi {$number} untuk {$item} berlaku s.d. {$warrantyUntil}. Apakah barangnya masih baik? — {$store}";
    }
}
