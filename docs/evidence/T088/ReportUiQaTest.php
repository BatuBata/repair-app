<?php

namespace Tests\T088Qa;

use App\Livewire\Reports\Index;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportUiQaTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_and_negative_net_keep_read_only_report_semantics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $component = Livewire::test(Index::class)->set('from', '2026-11-19')->set('to', '2026-11-19');
        $this->assertNetState($component->html(), 'Rp0', false);

        Expense::create(['spent_on' => '2026-11-19', 'amount' => 1, 'category' => 'bensin', 'created_by' => $admin->id]);
        $component->call('$refresh')->assertViewHas('report', fn ($report) => $report['net'] === '-1');
        $this->assertNetState($component->html(), '−Rp1', true);
    }

    private function assertNetState(string $html, string $money, bool $negative): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $net = $xpath->query('//dl[contains(concat(" ",normalize-space(@class)," ")," report-net ")]');
        $this->assertCount(1, $net);
        $this->assertSame($money, trim($xpath->query('./dd', $net->item(0))->item(0)->textContent));
        $this->assertSame($negative, str_contains($net->item(0)->getAttribute('class'), 'is-negative'));
        $this->assertSame('Laba bersih', trim($xpath->query('./dt', $net->item(0))->item(0)->textContent));
        $this->assertStringNotContainsString('wire:submit', $html);
        $this->assertSame(1, $xpath->query('//section[@aria-labelledby="report-period-title"]')->length);
        $this->assertSame(1, $xpath->query('//section[@aria-labelledby="report-transactions"]')->length);
        $this->assertStringContainsString('Filter jenis, metode, dan bank hanya berlaku untuk daftar transaksi ini.', $html);
        $this->assertStringContainsString('wire:loading.class="report-loading"', $html);
        $this->assertStringContainsString('Memuat laporan…', $html);
    }
}
