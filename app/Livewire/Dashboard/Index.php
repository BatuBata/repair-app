<?php

namespace App\Livewire\Dashboard;

use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\WhatsApp;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public string $series = 'revenue';

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    /**
     * @template T of ServiceOrder|Sale
     *
     * @param  Builder<T>  $query
     * @return Builder<T>
     */
    private function warranties(Builder $query, CarbonImmutable $today): Builder
    {
        if ($query->getModel() instanceof ServiceOrder) {
            $query->whereIn('status', ['selesai', 'diambil'])->whereNotNull('invoiced_on');
        }

        return $query->where('warranty_days', '>', 0)->whereNull('warranty_contacted_at')
            ->whereDate('warranty_until', '>=', $today->toDateString())
            ->whereDate('warranty_until', '<=', $today->addDays(3)->toDateString());
    }

    public function markContacted(mixed $kind, mixed $id): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['kind' => $kind, 'id' => $id], [
            'kind' => ['required', Rule::in(['service', 'sale'])],
            'id' => ['required', 'integer', 'min:1'],
        ], ['kind.in' => 'Jenis pengingat tidak valid.', 'id.integer' => 'Pengingat tidak valid.', 'id.min' => 'Pengingat tidak valid.'])->validate();
        DB::transaction(function () use ($data) {
            User::activeAccount(admin: true);
            $query = $data['kind'] === 'service' ? ServiceOrder::query() : Sale::query();
            $record = $query->whereKey($data['id'])->lockForUpdate()->first();
            $now = CarbonImmutable::now('Asia/Makassar');
            $eligible = $data['kind'] === 'service'
                ? $this->warranties(ServiceOrder::query(), $now)
                : $this->warranties(Sale::query(), $now);
            if (! $record || ! $eligible->whereKey($data['id'])->exists()) {
                throw ValidationException::withMessages(['reminder' => 'Pengingat tidak lagi tersedia. Daftar sudah diperbarui.']);
            }
            $record->update(['warranty_contacted_at' => $now]);
        });
        session()->flash('dashboard-status', 'Garansi ditandai sudah dihubungi.');
    }

    private static function number(BigInteger $amount): string
    {
        $digits = (string) $amount;

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits) ?? $digits;
    }

    private static function rupiah(BigInteger $amount): string
    {
        return 'Rp'.self::number($amount);
    }

    public function selectSeries(mixed $series): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['series' => $series], ['series' => ['required', Rule::in(['revenue', 'profit', 'invoices'])]], ['series.in' => 'Seri grafik tidak valid.'])->validate();
        $this->series = $data['series'];
        $this->resetValidation('series');
    }

    /** @return array<string, array<string, BigInteger>> */
    private function monthly(CarbonImmutable $today): array
    {
        $start = $today->startOfMonth()->toDateString();
        $end = $today->toDateString();
        $days = [];
        for ($date = $today->startOfMonth(); $date <= $today; $date = $date->addDay()) {
            $days[$date->toDateString()] = array_fill_keys(['revenue', 'profit', 'invoices', 'service', 'sales', 'service_profit', 'sales_profit'], BigInteger::zero());
        }
        $queries = [
            'service' => DB::table('service_order_items as items')->join('service_orders as orders', 'orders.id', '=', 'items.service_order_id')
                ->whereIn('orders.status', ['selesai', 'diambil'])->whereDate('orders.invoiced_on', '>=', $start)->whereDate('orders.invoiced_on', '<=', $end)
                ->select(['orders.invoiced_on as day', 'items.qty', 'items.price', 'items.cost']),
            'sales' => DB::table('sale_items as items')->join('sales', 'sales.id', '=', 'items.sale_id')
                ->whereDate('sales.sold_on', '>=', $start)->whereDate('sales.sold_on', '<=', $end)
                ->select(['sales.sold_on as day', 'items.qty', 'items.price', 'items.cost']),
        ];
        foreach ($queries as $kind => $query) {
            foreach ($query->cursor() as $row) {
                $day = substr($row->day, 0, 10);
                if (! isset($days[$day])) {
                    throw new \LogicException('Tanggal transaksi di luar rentang dashboard.');
                }
                $price = BigInteger::of($row->qty)->multipliedBy($row->price);
                $profit = $price->minus(BigInteger::of($row->qty)->multipliedBy($row->cost));
                $days[$day]['revenue'] = $days[$day]['revenue']->plus($price);
                $days[$day]['profit'] = $days[$day]['profit']->plus($profit);
                $days[$day][$kind] = $days[$day][$kind]->plus($price);
                $days[$day][$kind.'_profit'] = $days[$day][$kind.'_profit']->plus($profit);
            }
        }
        foreach (ServiceOrder::query()->whereIn('status', ['selesai', 'diambil'])->whereDate('invoiced_on', '>=', $start)->whereDate('invoiced_on', '<=', $end)->select('invoiced_on')->cursor() as $order) {
            $day = substr($order->getRawOriginal('invoiced_on'), 0, 10);
            if (! isset($days[$day])) {
                throw new \LogicException('Tanggal nota di luar rentang dashboard.');
            }
            $days[$day]['invoices'] = $days[$day]['invoices']->plus(1);
        }

        return $days;
    }

    /**
     * @param  array<string, array<string, BigInteger>>  $month
     * @return array<string, mixed>
     */
    private function chart(array $month): array
    {
        $totals = array_fill_keys(['revenue', 'profit', 'invoices'], BigInteger::zero());
        $min = $max = BigInteger::zero();
        foreach ($month as $day) {
            foreach ($totals as $key => $sum) {
                $totals[$key] = $sum->plus($day[$key]);
            }
            if ($day[$this->series]->isLessThan($min)) {
                $min = $day[$this->series];
            }
            if ($day[$this->series]->isGreaterThan($max)) {
                $max = $day[$this->series];
            }
        }
        $range = $max->minus($min);
        $lo = $hi = BigInteger::zero();
        $step = BigInteger::of(1);
        if (! $range->isZero()) {
            $rough = $range->plus(3)->quotient(4);
            $power = BigInteger::of(10)->power(strlen((string) $rough) - 1);
            foreach ([1, 2, 5, 10] as $multiple) {
                $step = $power->multipliedBy($multiple);
                if ($step->isGreaterThanOrEqualTo($rough)) {
                    break;
                }
            }
            $lo = $min->dividedBy($step, RoundingMode::Floor)->multipliedBy($step);
            $hi = $max->isPositive() ? $max->quotient($step)->plus(1)->multipliedBy($step) : BigInteger::zero();
        }
        $span = $hi->minus($lo);
        // Only the bounded ratio becomes a float for SVG pixels; money/tick/tooltip values stay exact integers.
        $y = fn (BigInteger $v): float => $span->isZero() ? 220.0 : round($hi->minus($v)->toBigDecimal()->dividedBy($span, 8, RoundingMode::HalfUp)->toFloat() * 220, 3);
        $format = fn (BigInteger $v): string => $this->series === 'invoices' ? self::number($v) : self::rupiah($v);
        $absoluteMax = $max->abs()->isGreaterThan($min->abs()) ? $max->abs() : $min->abs();
        $exponent = max(0, intdiv(strlen((string) $absoluteMax) - 1, 3)) * 3;
        $scale = BigInteger::of(10)->power($exponent);
        $scaleLabel = $this->series === 'invoices'
            ? ($exponent === 0 ? 'Nota servis' : 'Nota servis ×10^'.$exponent)
            : match ($exponent) {
                0 => 'Rupiah', 3 => 'Ribu Rupiah', 6 => 'Juta Rupiah', 9 => 'Miliar Rupiah', 12 => 'Triliun Rupiah', default => 'Rupiah ×10^'.$exponent
            };
        $ticks = [];
        for ($value = $lo; $value->isLessThanOrEqualTo($hi); $value = $value->plus($step)) {
            $decimal = (string) $value->toBigDecimal()->dividedBy($scale, 3, RoundingMode::Unnecessary)->strippedOfTrailingZeros();
            $parts = explode('.', $decimal);
            $label = (preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $parts[0]) ?? $parts[0]).(isset($parts[1]) ? ','.$parts[1] : '');
            $ticks[] = ['raw' => (string) $value, 'label' => $label, 'y' => $y($value)];
        }
        $points = [];
        $labels = [];
        $polyline = [];
        $i = 0;
        foreach ($month as $date => $day) {
            $x = count($month) === 1 ? 500.0 : round($i / (count($month) - 1) * 1000, 3);
            $yy = $y($day[$this->series]);
            $value = $format($day[$this->series]);
            $dateValue = CarbonImmutable::parse($date);
            $dateValue->locale('id');
            $dateLabel = $dateValue->translatedFormat('d M Y');
            $points[] = ['date' => $date, 'date_label' => $dateLabel, 'day' => (int) substr($date, 8, 2), 'x' => $x, 'y' => $yy, 'raw' => array_map(fn ($v) => (string) $v, $day), 'value' => $value,
                'service' => self::rupiah($day[$this->series === 'profit' ? 'service_profit' : 'service']), 'sales' => self::rupiah($day[$this->series === 'profit' ? 'sales_profit' : 'sales'])];
            $labels[] = $dateLabel.': '.$value;
            $polyline[] = $x.','.$yy;
            $i++;
        }

        return ['totals' => ['revenue' => self::rupiah($totals['revenue']), 'profit' => self::rupiah($totals['profit']), 'invoices' => self::number($totals['invoices'])],
            'scale_exponent' => $exponent, 'scale_label' => $scaleLabel, 'scale_factor' => self::number($scale), 'min' => (string) $min, 'max' => (string) $max, 'ticks' => $ticks, 'points' => $points, 'labels' => $labels,
            'polyline' => implode(' ', $polyline), 'empty' => $range->isZero() && $max->isZero()];
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        Validator::make(['series' => $this->series], ['series' => ['required', Rule::in(['revenue', 'profit', 'invoices'])]])->validate();
        $today = CarbonImmutable::now('Asia/Makassar')->startOfDay();
        $start = $today->setTimezone(config('app.timezone'))->toDateTimeString();
        $end = $today->addDay()->setTimezone(config('app.timezone'))->toDateTimeString();
        $incoming = ServiceOrder::query()->where('created_at', '>=', $start)->where('created_at', '<', $end)->count();
        $counts = ServiceOrder::query()->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $month = $this->monthly($today);
        $daily = $month[$today->toDateString()];
        $revenue = ['total' => self::rupiah($daily['revenue']), 'service' => self::rupiah($daily['service']), 'sales' => self::rupiah($daily['sales'])];
        $chart = $this->chart($month);
        $lane = [];
        foreach (['diterima', 'dikerjakan', 'selesai', 'diambil'] as $status) {
            $query = ServiceOrder::query()->where('status', $status);
            if ($status === 'diambil') {
                $query->whereDate('picked_up_on', '>=', $today->startOfMonth()->toDateString())->whereDate('picked_up_on', '<=', $today->toDateString());
            }
            $count = $query->count();
            $tags = $query->orderBy('created_at')->limit(3)->get(['number', 'created_at', 'assigned_at', 'invoiced_on', 'picked_up_on'])->map(function ($order) use ($today, $status) {
                $date = match ($status) {
                    'dikerjakan' => $order->assigned_at, 'selesai' => $order->invoiced_on, 'diambil' => $order->picked_up_on, default => $order->created_at
                };
                $age = $date ? max(0, (int) CarbonImmutable::parse($date)->startOfDay()->diffInDays($today)) : 0;

                return ['suffix' => substr($order->number, strrpos($order->number, '-') + 1), 'age' => $age];
            });
            $lane[$status] = ['count' => $count, 'tags' => $tags];
        }
        $backlog = ServiceOrder::query()->where('status', 'selesai')->with('category:id,name')->orderBy('invoiced_on')->orderBy('id')->limit(5)->get();
        $store = StoreSetting::query()->value('name') ?: 'Anugerah';
        $reminders = collect();
        $records = $this->warranties(ServiceOrder::query(), $today)->with('category:id,name')->get()->toBase()
            ->concat($this->warranties(Sale::query(), $today)->with('items:id,sale_id,product_name')->get());
        foreach ($records as $record) {
            $kind = $record instanceof ServiceOrder ? 'service' : 'sale';
            $item = $record instanceof ServiceOrder ? trim($record->category->name.' '.$record->item_description) : $record->items->pluck('product_name')->implode(', ');
            $until = CarbonImmutable::parse($record->getRawOriginal('warranty_until'), 'Asia/Makassar');
            $until->locale('id');
            $reminders->push([
                'kind' => $kind, 'id' => $record->id, 'name' => $record->customer_name ?: 'Tanpa nama', 'number' => $record->number,
                'item' => $item, 'until' => $until, 'days' => (int) $today->diffInDays($until),
                'url' => WhatsApp::url($record->customer_phone, WhatsApp::warrantyMessage($record->customer_name ?: 'Customer', $record->number, $item, $until->translatedFormat('d M Y'), $store)),
                'detail' => route($kind === 'service' ? 'tts.show' : 'sales.show', $record->id),
            ]);
        }
        $reminders = $reminders->sortBy(fn ($r) => $r['until']->toDateString().$r['number'])->values();

        return view('livewire.dashboard.index', compact('today', 'incoming', 'counts', 'lane', 'revenue', 'backlog', 'reminders', 'chart'))->layout('layouts.app', ['title' => 'Beranda']);
    }
}
