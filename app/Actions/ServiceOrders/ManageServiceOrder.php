<?php

namespace App\Actions\ServiceOrders;

use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\ServiceOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ManageServiceOrder
{
    protected function locked(mixed $id): ServiceOrder
    {
        User::activeAccount(admin: true);
        $id = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1']])->validate()['id'];

        return ServiceOrder::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function assign(mixed $id, mixed $technicianId): void
    {
        DB::transaction(function () use ($id, $technicianId) {
            $order = $this->locked($id);
            if (! in_array($order->getAttribute('status'), [ServiceOrderStatus::Diterima, ServiceOrderStatus::Dikerjakan], true)) {
                throw ValidationException::withMessages(['status' => 'Teknisi hanya dapat ditetapkan sebelum nota terbit.']);
            }
            $data = Validator::make(['technician_id' => $technicianId], ['technician_id' => ['required', 'integer', 'min:1']])->validate();
            $technician = User::query()->whereKey($data['technician_id'])->where('role', UserRole::Teknisi)->where('is_active', true)->lockForUpdate()->first();
            if (! $technician) {
                throw ValidationException::withMessages(['technician_id' => 'Pilih teknisi yang aktif.']);
            }
            if ($order->getAttribute('status') === ServiceOrderStatus::Dikerjakan && $order->technician_id === $technician->id) {
                return;
            }
            if ($order->getAttribute('status') === ServiceOrderStatus::Diterima) {
                $order->getAttribute('status')->assertTransitionTo(ServiceOrderStatus::Dikerjakan, UserRole::Admin);
            }
            $order->update(['technician_id' => $technician->id, 'status' => ServiceOrderStatus::Dikerjakan, 'assigned_at' => CarbonImmutable::now('Asia/Makassar')]);
        }, 5);
    }

    /** @param array<string, mixed> $input */
    public function invoice(mixed $id, array $input, bool $correction, string $revision): void
    {
        DB::transaction(function () use ($id, $input, $correction, $revision) {
            $order = $this->locked($id);
            $status = $order->getAttribute('status');
            if (($correction && ! in_array($status, [ServiceOrderStatus::Selesai, ServiceOrderStatus::Diambil], true)) || (! $correction && $status !== ServiceOrderStatus::Dikerjakan)) {
                throw ValidationException::withMessages(['status' => 'Status TTS sudah berubah. Buka kembali rincian nota.']);
            }
            if (! $order->technician_id || ($correction && ! $order->invoiced_on)) {
                throw ValidationException::withMessages(['status' => 'Teknisi dan data nota harus tersedia.']);
            }
            if (! hash_equals(InvoiceLines::revision($order), $revision)) {
                throw ValidationException::withMessages(['status' => 'Data sudah berubah. Muat ulang sebelum menyimpan nota.']);
            }
            $data = Validator::make($input, [
                'rows' => ['required', 'array', 'list', 'min:1'],
                'rows.*' => ['array:type,description,qty,price,cost'],
                'rows.*.description' => ['required', 'string', 'max:255'],
                'warranty_days' => ['required', 'integer', 'min:0', 'max:4294967295'],
                'unrepairable' => ['required', 'boolean'],
            ], [
                'required' => ':attribute wajib diisi.', 'min' => ':attribute minimal :min.', 'max' => ':attribute maksimal :max.',
                'integer' => ':attribute harus berupa bilangan bulat.', 'array' => ':attribute tidak valid.',
                'list' => ':attribute harus berupa daftar.', 'string' => ':attribute harus berupa teks.',
            ], ['rows' => 'Rincian pekerjaan', 'rows.*.description' => 'Deskripsi', 'warranty_days' => 'Lama garansi', 'unrepairable' => 'Kondisi barang'])->validate();
            $lines = InvoiceLines::calculate($data['rows']);
            $invoiceDate = $correction ? $order->getAttribute('invoiced_on') : CarbonImmutable::today('Asia/Makassar');
            $days = (int) $data['warranty_days'];
            $until = self::warrantyUntil($invoiceDate, $days);
            $order->items()->delete();
            $order->items()->createMany($lines['rows']);
            $order->update([
                'status' => $correction ? $status : ServiceOrderStatus::Selesai,
                'invoiced_on' => $invoiceDate->toDateString(), 'warranty_days' => $days,
                'warranty_until' => $until->toDateString(), 'unrepairable' => $data['unrepairable'],
            ]);
        }, 5);
    }

    public static function warrantyUntil(CarbonImmutable $invoiceDate, int $days): CarbonImmutable
    {
        $lastDate = CarbonImmutable::create(9999, 12, 31, 0, 0, 0, 'Asia/Makassar');
        if ($days < 0 || $invoiceDate->year < 1000 || $invoiceDate->greaterThan($lastDate) || $days > (int) $invoiceDate->diffInDays($lastDate)) {
            throw ValidationException::withMessages(['warranty_days' => 'Tanggal akhir garansi melewati batas tanggal yang dapat disimpan.']);
        }

        return $invoiceDate->addDays($days);
    }

    public function collect(mixed $id, mixed $date): void
    {
        DB::transaction(function () use ($id, $date) {
            $order = $this->locked($id);
            $order->getAttribute('status')->assertTransitionTo(ServiceOrderStatus::Diambil, UserRole::Admin);
            $data = Validator::make(['picked_up_on' => $date], ['picked_up_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31']], ['*' => 'Isi tanggal diambil yang valid (tahun 1000–9999).'])->validate();
            $order->update(['status' => ServiceOrderStatus::Diambil, 'picked_up_on' => $data['picked_up_on']]);
        }, 5);
    }

    public function cancel(mixed $id, mixed $reason): void
    {
        DB::transaction(function () use ($id, $reason) {
            $order = $this->locked($id);
            $order->getAttribute('status')->assertTransitionTo(ServiceOrderStatus::Batal, UserRole::Admin);
            $data = Validator::make(['cancel_reason' => $reason], ['cancel_reason' => ['nullable', 'string', 'max:500']])->validate();
            $order->update(['status' => ServiceOrderStatus::Batal, 'cancel_reason' => $data['cancel_reason'] ?? null, 'cancelled_at' => CarbonImmutable::now('Asia/Makassar')]);
        }, 5);
    }
}
