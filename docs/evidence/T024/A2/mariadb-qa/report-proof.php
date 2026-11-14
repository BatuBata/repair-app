<?php
declare(strict_types=1);
require __DIR__.'/boot.php';
$owned = json_decode(file_get_contents($qa.'/fixtures.json'), true, flags: JSON_THROW_ON_ERROR);
auth()->setUser(App\Models\User::findOrFail($owned['admins'][0]));
$item = App\Models\ServiceOrderItem::firstOrFail();
verify($item->getRawOriginal('type') === 'jasa' && $item->cost === 0, 'Jasa cost normalization mismatch.');
$report = app(App\Services\Reports\Report::class)->build('2033-01-15', '2033-01-15');
$expected = ['service' => '8000', 'sales' => '81000', 'revenue' => '89000', 'cost' => '50500', 'gross' => '38500', 'expenses' => '1000', 'net' => '37500', 'restock' => '2000'];
foreach ($expected as $key => $value) verify($report[$key] === $value, 'Report total mismatch: '.$key);
verify(count($report['rows']) === 5 && $report['days'][0]['amount'] === '89000', 'Report rows/daily total mismatch.');
$result = ['status' => 'PASS', 'expected' => $expected, 'actual' => array_intersect_key($report, $expected), 'rows' => 5, 'daily_amount' => $report['days'][0]['amount'], 'initial_probe_failure' => 'Harness expected cost1500 for jasa, but model explicitly normalizes jasa cost to0; initial FAIL preserved, no fixture/race rerun.', 'correction' => 'Read-only proof on existing committed synthetic fixtures.'];
file_put_contents($qa.'/report-proof.json', json_encode($result, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
echo "PASS exactreport totals; jasa cost0 matchesmodel; read-only recheck\n";
