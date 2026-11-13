<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StockEntry;
use App\Models\User;
use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class Report
{
    public static function money(string $amount): string
    {
        $negative = str_starts_with($amount, '-');
        $digits = ltrim($amount, '-');

        return ($negative ? '−' : '').'Rp'.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits);
    }

    /** @return array<string, mixed> */
    public function build(string $from, string $to, int $page = 0): array
    {
        User::activeAccount(admin: true);
        Validator::make(compact('from', 'to'), self::dateRules())->validate();
        $rows = [];
        foreach (ServiceOrder::query()->whereIn('status', ['selesai', 'diambil'])->whereDate('invoiced_on', '>=', $from)->whereDate('invoiced_on', '<=', $to)->with('items')->get() as $order) {
            $lines = [];
            foreach ($order->items as $item) {
                $lines[] = ['description' => $item->description, 'qty' => (string) $item->qty, 'amount' => (string) BigInteger::of($item->qty)->multipliedBy($item->price), 'cost' => (string) BigInteger::of($item->qty)->multipliedBy($item->cost)];
            }
            $rows[] = $this->transaction('servis', $order->id, CarbonImmutable::parse((string) $order->getRawOriginal('invoiced_on'))->toDateString(), $order->number, $order->customer_name.' · '.($order->item_description ?? 'Servis'), $lines, route('tts.show', $order->id));
        }
        foreach (Sale::query()->whereDate('sold_on', '>=', $from)->whereDate('sold_on', '<=', $to)->with('items')->get() as $sale) {
            $lines = [];
            foreach ($sale->items as $item) {
                $lines[] = ['description' => $item->product_name, 'qty' => (string) $item->qty, 'amount' => (string) BigInteger::of($item->qty)->multipliedBy($item->price), 'cost' => (string) BigInteger::of($item->qty)->multipliedBy($item->cost)];
            }
            $rows[] = $this->transaction('jual', $sale->id, CarbonImmutable::parse((string) $sale->getRawOriginal('sold_on'))->toDateString(), $sale->number, $sale->customer_name ?: 'Penjualan langsung', $lines, route('sales.show', $sale->id));
        }
        $expenseTotal = BigInteger::zero();
        foreach (Expense::query()->whereDate('spent_on', '>=', $from)->whereDate('spent_on', '<=', $to)->get() as $expense) {
            $expenseTotal = $expenseTotal->plus($expense->amount);
            $rows[] = $this->transaction('keluar', $expense->id, CarbonImmutable::parse((string) $expense->getRawOriginal('spent_on'))->toDateString(), ucfirst((string) $expense->getRawOriginal('category')), $expense->description ?: 'Tanpa keterangan', [['description' => $expense->description ?: ucfirst((string) $expense->getRawOriginal('category')), 'qty' => '1', 'amount' => (string) -$expense->amount, 'cost' => '0']], null);
        }
        usort($rows, fn (array $a, array $b): int => strcmp($b['date'], $a['date']) ?: strcmp($a['key'], $b['key']));
        $service = BigInteger::zero();
        $sales = BigInteger::zero();
        $cost = BigInteger::zero();
        $daily = [];
        foreach ($rows as $row) {
            if ($row['type'] === 'keluar') {
                continue;
            }
            $row['type'] === 'servis' ? $service = $service->plus($row['amount']) : $sales = $sales->plus($row['amount']);
            $cost = $cost->plus($row['cost']);
            $daily[$row['date']] ??= ['servis' => '0', 'jual' => '0'];
            $daily[$row['date']][$row['type']] = (string) BigInteger::of($daily[$row['date']][$row['type']])->plus($row['amount']);
        }
        $restock = BigInteger::zero();
        foreach (StockEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->cursor() as $entry) {
            $restock = $restock->plus(BigInteger::of($entry->qty)->multipliedBy($entry->cost_price));
        }
        $revenue = $service->plus($sales);
        $gross = $revenue->minus($cost);
        $start = CarbonImmutable::parse($from, 'Asia/Makassar');
        $end = CarbonImmutable::parse($to, 'Asia/Makassar');
        $dayCount = (int) $start->diffInDays($end) + 1;
        $lastPage = intdiv($dayCount - 1, 31);
        $page = max(0, min($page, $lastPage));
        $highest = BigInteger::zero();
        foreach ($daily as $day) {
            $total = BigInteger::of($day['servis'])->plus($day['jual']);
            if ($total->isGreaterThan($highest)) {
                $highest = $total;
            }
        }
        $axisUnit = $highest->isGreaterThanOrEqualTo('1000000000000') ? BigInteger::of('1000000000000') : BigInteger::of(1);
        $step = BigInteger::of(500000);
        if ($highest->isGreaterThan(3000000)) {
            $step = $highest->plus(2499999)->quotient(2500000)->multipliedBy(500000);
        }
        if ($axisUnit->isGreaterThan(1)) {
            $step = $highest->plus($axisUnit->multipliedBy(5)->minus(1))->quotient($axisUnit->multipliedBy(5))->multipliedBy($axisUnit);
        }
        $ceiling = $highest->plus($step)->quotient($step)->multipliedBy($step);
        $average = $revenue->quotient($dayCount);
        $days = [];
        for ($i = $page * 31; $i < min($dayCount, ($page + 1) * 31); $i++) {
            $date = $start->addDays($i);
            $day = $daily[$date->toDateString()] ?? ['servis' => '0', 'jual' => '0'];
            $amount = BigInteger::of($day['servis'])->plus($day['jual']);
            $days[] = [...$day, 'date' => $date->toDateString(), 'label' => $date->format('j'), 'amount' => (string) $amount, 'height' => $amount->multipliedBy(10000)->quotient($ceiling)->toInt() / 100, 'today' => $date->isSameDay(CarbonImmutable::now('Asia/Makassar'))];
        }
        $ticks = [];
        for ($tick = BigInteger::zero(); $tick->isLessThanOrEqualTo($ceiling); $tick = $tick->plus($step)) {
            $ticks[] = ['label' => self::money((string) $tick->quotient($axisUnit)).($axisUnit->isGreaterThan(1) ? ' T' : ''), 'bottom' => $tick->multipliedBy(10000)->quotient($ceiling)->toInt() / 100];
        }

        return ['rows' => $rows, 'service' => (string) $service, 'sales' => (string) $sales, 'revenue' => (string) $revenue, 'cost' => (string) $cost, 'gross' => (string) $gross, 'expenses' => (string) $expenseTotal, 'net' => (string) $gross->minus($expenseTotal), 'restock' => (string) $restock, 'days' => $days, 'average' => (string) $average, 'averageRemainder' => (string) $revenue->remainder($dayCount), 'dayCount' => $dayCount, 'highest' => (string) $highest, 'averageHeight' => $average->multipliedBy(10000)->quotient($ceiling)->toInt() / 100, 'ticks' => $ticks, 'axisTrillions' => $axisUnit->isGreaterThan(1), 'page' => $page, 'lastPage' => $lastPage];
    }

    /** @return array<string, list<string>> */
    public static function dateRules(): array
    {
        return ['from' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:9999-12-31']];
    }

    /**
     * @param  list<array{description: string, qty: string, amount: string, cost: string}>  $lines
     * @return array{key: string, type: string, date: string, number: string, description: string, amount: string, cost: string, profit: string, lines: list<array{description: string, qty: string, amount: string, cost: string}>, url: ?string}
     */
    private function transaction(string $type, int $id, string $date, string $number, string $description, array $lines, ?string $url): array
    {
        $amount = BigInteger::zero();
        $cost = BigInteger::zero();
        foreach ($lines as $line) {
            $amount = $amount->plus($line['amount']);
            $cost = $cost->plus($line['cost']);
        }

        return ['key' => $type.'-'.$id, 'type' => $type, 'date' => $date, 'number' => $number, 'description' => $description, 'amount' => (string) $amount, 'cost' => (string) $cost, 'profit' => (string) $amount->minus($cost), 'lines' => $lines, 'url' => $url];
    }
}
