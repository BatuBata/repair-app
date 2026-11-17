<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Services\Reports\Report;
use App\Support\Payment;
use Brick\Math\BigInteger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class Index extends Component
{
    public string $from;

    public string $to;

    public string $type = 'semua';

    public string $payment_method = 'semua';

    public string $payment_bank = 'semua';

    public int $chartPage = 0;

    public ?string $detail = null;

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function mount(): void
    {
        User::activeAccount(admin: true);
        $this->from = now('Asia/Makassar')->startOfMonth()->toDateString();
        $this->to = now('Asia/Makassar')->toDateString();
    }

    public function period(string $period): void
    {
        User::activeAccount(admin: true);
        abort_unless(in_array($period, ['today', 'week', 'month'], true), 422);
        $today = now('Asia/Makassar');
        $this->to = $today->toDateString();
        $this->from = match ($period) {
            'today' => $today->toDateString(),
            'week' => $today->subDays(6)->toDateString(),
            default => $today->startOfMonth()->toDateString(),
        };
        $this->chartPage = 0;
        $this->detail = null;
    }

    public function updated(string $property): void
    {
        User::activeAccount(admin: true);
        if (in_array($property, ['from', 'to', 'type', 'payment_method', 'payment_bank'], true)) {
            $this->chartPage = 0;
            $this->detail = null;
        }
        if ($property === 'payment_method' && $this->payment_method !== 'transfer') {
            $this->payment_bank = 'semua';
        }
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $filters = Validator::make(['from' => $this->from, 'to' => $this->to, 'type' => $this->type, 'payment_method' => $this->payment_method, 'payment_bank' => $this->payment_bank], [...Report::dateRules(), 'type' => ['in:semua,servis,jual,keluar'], 'payment_method' => ['in:semua,'.implode(',', array_keys(Payment::METHODS))], 'payment_bank' => ['in:semua,'.implode(',', Payment::BANKS)]], [
            'from.required' => 'Dari tanggal wajib diisi.', 'to.required' => 'Sampai tanggal wajib diisi.',
            'from.date_format' => 'Dari tanggal harus menggunakan format YYYY-MM-DD.', 'to.date_format' => 'Sampai tanggal harus menggunakan format YYYY-MM-DD.',
            'from.after_or_equal' => 'Dari tanggal paling awal 1000-01-01.', 'from.before_or_equal' => 'Dari tanggal paling akhir 9999-12-31.',
            'to.after_or_equal' => 'Sampai tanggal harus sama atau setelah Dari tanggal.', 'to.before_or_equal' => 'Sampai tanggal paling akhir 9999-12-31.', 'type.in' => 'Jenis transaksi tidak valid.',
            'payment_method.in' => 'Metode pembayaran tidak valid.', 'payment_bank.in' => 'Bank pembayaran tidak valid.',
        ]);
        $report = $filters->fails() ? null : app(Report::class)->build($this->from, $this->to, $this->chartPage);
        $rows = [];
        $selected = null;
        $totals = ['amount' => '0', 'cost' => '0', 'profit' => '0'];
        foreach ($report['rows'] ?? [] as $row) {
            if ($row['key'] === $this->detail) {
                $selected = $row;
            }
            if ($this->type !== 'semua' && $row['type'] !== $this->type) {
                continue;
            }
            if ($this->payment_method !== 'semua' && $row['payment_method'] !== $this->payment_method) {
                continue;
            }
            if ($this->payment_bank !== 'semua' && $row['payment_bank'] !== $this->payment_bank) {
                continue;
            }
            $rows[] = $row;
            foreach ($totals as $key => $total) {
                $totals[$key] = (string) BigInteger::of($total)->plus($row[$key]);
            }
        }

        return view('livewire.reports.index', ['report' => $report, 'rows' => $rows, 'totals' => $totals, 'selected' => $selected, 'filterErrors' => $filters->errors(), 'paymentMethods' => Payment::methodOptions(all: true), 'paymentBanks' => Payment::bankOptions(all: true)])->layout('layouts.app', ['title' => 'Laporan']);
    }
}
