<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DatePickerTest extends TestCase
{
    public function test_date_picker_renders_shared_accessible_calendar_contract(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.date-picker name="sold_on" label="Tanggal jual" value="2026-11-15" min="2026-01-01" max="2026-12-31" wire:model.live="sold_on" required />
        BLADE);

        $this->assertStringContainsString('class="field date-picker"', $html);
        $this->assertStringContainsString('class="date-picker-trigger"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('role="grid"', $html);
        $this->assertStringContainsString('role="row"', $html);
        $this->assertStringContainsString('role="gridcell"', $html);
        $this->assertStringContainsString('aria-haspopup="dialog"', $html);
        $this->assertStringContainsString('aria-required="true"', $html);
        $this->assertStringContainsString('wire:model.live="sold_on"', $html);
        $this->assertStringContainsString('x-bind:style="open ? popoverStyle() : \'\'"', $html);
        $this->assertStringContainsString('requestAnimationFrame(() => this.focusButton())', $html);
        $this->assertStringContainsString('2026-11-15', $html);
        $this->assertStringContainsString('Hari ini', $html);
        $this->assertStringNotContainsString('type="date"', $html);
    }

    public function test_every_application_date_control_uses_the_shared_picker(): void
    {
        $views = collect(glob(resource_path('views/livewire/**/*.blade.php')))
            ->merge(glob(resource_path('views/livewire/*.blade.php')))
            ->mapWithKeys(fn (string $path) => [$path => file_get_contents($path)]);

        $this->assertFalse($views->contains(fn (string $source) => preg_match('/<input[^>]*type="date"/', $source) === 1));
        $this->assertSame(5, $views->sum(fn (string $source) => substr_count($source, '<x-ui.date-picker')));

        $this->assertStringContainsString("'sold_on' => ['Tanggal jual', 'date']", $views->first(fn (string $source, string $path) => str_ends_with($path, '/sales/form.blade.php')));
        $this->assertStringContainsString('name="entry_date" label="Tanggal masuk" type="date"', $views->first(fn (string $source, string $path) => str_ends_with($path, '/stock/restock.blade.php')));

        $field = file_get_contents(resource_path('views/components/ui/field.blade.php'));
        $this->assertStringContainsString("\$type === 'date'", $field);
        $this->assertStringContainsString('<x-ui.date-picker', $field);

        $css = file_get_contents(resource_path('css/layout.css'));
        $this->assertMatchesRegularExpression('/\.date-picker-grid button \{[^}]*height:44px;/', $css);
    }
}
