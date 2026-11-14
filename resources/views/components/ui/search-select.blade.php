@props([
    'name',
    'label',
    'options',
    'value' => '',
    'placeholder' => 'Cari dan pilih',
    'invalid' => false,
    'describedby' => null,
    'required' => false,
])
@php
    $items = collect($options)->map(fn ($option) => [
        'value' => (string) $option['value'],
        'label' => (string) $option['label'],
    ])->values();
    $id = 'search-select-'.preg_replace('/[^a-z0-9_-]+/i', '-', $name);
@endphp
<div
    class="field search-select"
    x-data="{
        options: @js($items),
        selected: @js((string) $value),
        query: '',
        open: false,
        active: 0,
        init() { this.query = this.selectedLabel() },
        selectedLabel() { return this.options.find(option => option.value === this.selected)?.label ?? '' },
        filtered() {
            const query = this.query.trim().toLocaleLowerCase('id')
            const selected = this.selectedLabel().toLocaleLowerCase('id')
            return query === '' || query === selected ? this.options : this.options.filter(option => option.label.toLocaleLowerCase('id').includes(query))
        },
        show() {
            this.open = true
            this.active = Math.max(0, this.filtered().findIndex(option => option.value === this.selected))
        },
        close() { this.open = false; this.query = this.selectedLabel() },
        search() {
            this.open = true
            this.active = 0
        },
        move(step) {
            const count = this.filtered().length
            if (count === 0) return
            this.open = true
            this.active = (this.active + step + count) % count
            this.$nextTick(() => this.$refs.list?.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }))
        },
        choose(option) {
            this.selected = option.value
            this.query = option.label
            this.open = false
            this.sync()
        },
        chooseActive() {
            const option = this.filtered()[this.active]
            if (option) this.choose(option)
        },
        sync() {
            this.$nextTick(() => {
                this.$refs.value.value = this.selected
                this.$refs.value.dispatchEvent(new Event('input', { bubbles: true }))
                this.$refs.value.dispatchEvent(new Event('change', { bubbles: true }))
            })
        },
    }"
    x-on:click.outside="close()"
>
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="search-select-control">
        <input
            class="input"
            id="{{ $id }}"
            type="search"
            x-model="query"
            x-on:focus="show()"
            x-on:click="show()"
            x-on:input="search()"
            x-on:keydown.arrow-down.prevent="move(1)"
            x-on:keydown.arrow-up.prevent="move(-1)"
            x-on:keydown.enter.prevent="chooseActive()"
            x-on:keydown.escape.prevent="close()"
            x-on:keydown.tab="close()"
            role="combobox"
            aria-autocomplete="list"
            x-bind:aria-expanded="open"
            aria-controls="{{ $id }}-options"
            x-bind:aria-activedescendant="open && filtered()[active] ? '{{ $id }}-option-' + active : null"
            aria-invalid="{{ $invalid ? 'true' : 'false' }}"
            @if($describedby) aria-describedby="{{ $describedby }}" @endif
            @if($required) aria-required="true" @endif
            placeholder="{{ $placeholder }}"
            autocomplete="off"
        >
        <input x-ref="value" type="hidden" name="{{ $name }}" x-bind:value="selected" {{ $attributes->whereStartsWith('wire:') }}>
        <div x-cloak x-show="open" class="search-select-menu" id="{{ $id }}-options" role="listbox" x-ref="list">
            <template x-for="(option, index) in filtered()" x-bind:key="option.value">
                <button
                    type="button"
                    role="option"
                    x-bind:id="'{{ $id }}-option-' + index"
                    x-bind:aria-selected="option.value === selected"
                    x-bind:data-active="index === active"
                    x-on:mouseenter="active = index"
                    x-on:mousedown.prevent="choose(option)"
                    x-text="option.label"
                ></button>
            </template>
            <p x-show="filtered().length === 0" class="search-select-empty" role="status">Tidak ada hasil</p>
        </div>
    </div>
</div>
