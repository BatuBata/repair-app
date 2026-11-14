<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SearchSelectTest extends TestCase
{
    public function test_search_select_renders_accessible_search_and_selected_value(): void
    {
        $html = Blade::render(
            '<x-ui.search-select name="product" label="Barang" :options="$options" value="2" placeholder="Cari barang" wire:model="product" required />',
            ['options' => [
                ['value' => 1, 'label' => 'Kabel HDMI'],
                ['value' => 2, 'label' => 'Remote TV'],
            ]],
        );

        $this->assertStringContainsString('type="search"', $html);
        $this->assertStringContainsString('role="combobox"', $html);
        $this->assertStringContainsString('role="listbox"', $html);
        $this->assertStringContainsString('aria-autocomplete="list"', $html);
        $this->assertStringContainsString('aria-required="true"', $html);
        $this->assertStringContainsString('placeholder="Cari barang"', $html);
        $this->assertStringContainsString('wire:model="product"', $html);
        $this->assertStringContainsString('Remote TV', $html);
        $this->assertStringContainsString('Tidak ada hasil', $html);
    }

    public function test_only_dynamic_or_more_than_three_choice_callers_use_search_select(): void
    {
        foreach ([
            'resources/views/livewire/sales/form.blade.php',
            'resources/views/livewire/tts/form.blade.php',
            'resources/views/livewire/tts/detail.blade.php',
            'resources/views/livewire/expenses/index.blade.php',
        ] as $path) {
            $this->assertStringContainsString('<x-ui.search-select', file_get_contents(base_path($path)), $path);
        }

        $roles = file_get_contents(resource_path('views/livewire/users/index.blade.php'));
        $this->assertStringContainsString('<select class="select" id="role"', $roles);
        $this->assertStringNotContainsString('<x-ui.search-select', $roles);
    }
}
