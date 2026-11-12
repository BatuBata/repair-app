<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class DocumentNumber
{
    public function next(string $prefix, ?DateTimeInterface $createdAt = null): string
    {
        if (! in_array($prefix, ['TTS', 'JL'], true)) {
            throw new InvalidArgumentException('Prefiks dokumen tidak dikenal.');
        }
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Nomor harus dibuat di dalam transaksi penyimpanan dokumen.');
        }
        $period = ($createdAt ? CarbonImmutable::instance($createdAt) : CarbonImmutable::now('Asia/Makassar'))
            ->setTimezone('Asia/Makassar')->format('ym');
        $key = ['prefix' => $prefix, 'period' => $period];
        // Insert first: the unique primary key serializes concurrent first use of a month.
        DB::table('number_sequences')->insertOrIgnore([...$key, 'last_seq' => 0]);
        $sequence = DB::table('number_sequences')->where($key)->lockForUpdate()->first();
        $next = $sequence->last_seq + 1;
        DB::table('number_sequences')->where($key)->update(['last_seq' => $next]);

        return $prefix.'-'.$period.'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
