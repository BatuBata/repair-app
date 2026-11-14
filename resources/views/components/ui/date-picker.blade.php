@props([
    'name',
    'label',
    'value' => '',
    'min' => '1000-01-01',
    'max' => '9999-12-31',
    'required' => false,
    'invalid' => false,
    'describedby' => null,
])
@php($id = 'date-picker-'.preg_replace('/[^a-z0-9_-]+/i', '-', $name))
<div
    class="field date-picker"
    x-data="{
        value: @js((string) $value),
        min: @js((string) $min),
        max: @js((string) $max),
        open: false,
        viewYear: 0,
        viewMonth: 0,
        focusDate: '',
        months: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        parse(iso) {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso)
            if (!match) return null
            const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
            return date.getFullYear() === Number(match[1]) && date.getMonth() === Number(match[2]) - 1 && date.getDate() === Number(match[3]) ? date : null
        },
        iso(date) {
            const pad = number => String(number).padStart(2, '0')
            return `${String(date.getFullYear()).padStart(4, '0')}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
        },
        bounded(iso) { return iso < this.min ? this.min : (iso > this.max ? this.max : iso) },
        today() { return this.iso(new Date()) },
        display() {
            const date = this.parse(this.value)
            if (!date) return 'Pilih tanggal'
            return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`
        },
        monthLabel() { return `${this.months[this.viewMonth]} ${this.viewYear}` },
        openCalendar() {
            const date = this.parse(this.value) ?? this.parse(this.bounded(this.today()))
            this.viewYear = date.getFullYear()
            this.viewMonth = date.getMonth()
            this.focusDate = this.iso(date)
            this.open = true
            this.$nextTick(() => this.focusButton())
        },
        close(returnFocus = false) {
            this.open = false
            if (returnFocus) this.$nextTick(() => this.$refs.trigger.focus())
        },
        days() {
            const first = new Date(this.viewYear, this.viewMonth, 1)
            const start = new Date(this.viewYear, this.viewMonth, 1 - ((first.getDay() + 6) % 7))
            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + index)
                const iso = this.iso(date)
                return {
                    iso,
                    day: date.getDate(),
                    currentMonth: date.getMonth() === this.viewMonth,
                    disabled: iso < this.min || iso > this.max,
                    label: new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(date),
                }
            })
        },
        weeks() {
            const days = this.days()
            return Array.from({ length: 6 }, (_, index) => days.slice(index * 7, index * 7 + 7))
        },
        canMoveMonth(step) {
            const target = new Date(this.viewYear, this.viewMonth + step, 1)
            if (target.getFullYear() < 1000 || target.getFullYear() > 9999) return false
            const first = this.iso(target)
            const last = this.iso(new Date(target.getFullYear(), target.getMonth() + 1, 0))
            return last >= this.min && first <= this.max
        },
        moveMonth(step) {
            if (!this.canMoveMonth(step)) return
            const date = new Date(this.viewYear, this.viewMonth + step, 1)
            this.viewYear = date.getFullYear()
            this.viewMonth = date.getMonth()
            this.focusDate = this.bounded(this.iso(date))
            this.focusAfterRender()
        },
        moveDay(step) {
            const current = this.parse(this.focusDate) ?? this.parse(this.today())
            const nextDate = new Date(current.getFullYear(), current.getMonth(), current.getDate() + step)
            if (nextDate.getFullYear() < 1000 || nextDate.getFullYear() > 9999) return
            const next = this.iso(nextDate)
            if (next < this.min || next > this.max) return
            const date = this.parse(next)
            this.focusDate = next
            this.viewYear = date.getFullYear()
            this.viewMonth = date.getMonth()
            this.focusAfterRender()
        },
        focusAfterRender() { this.$nextTick(() => requestAnimationFrame(() => this.focusButton())) },
        focusButton() { this.$refs.calendar?.querySelector(`[data-date='${this.focusDate}']`)?.focus() },
        select(day) {
            if (day.disabled) return
            this.value = day.iso
            this.sync()
            this.close(true)
        },
        selectToday() {
            const iso = this.today()
            this.select({ iso, disabled: iso < this.min || iso > this.max })
        },
        sync() {
            this.$nextTick(() => {
                this.$refs.value.value = this.value
                this.$refs.value.dispatchEvent(new Event('input', { bubbles: true }))
                this.$refs.value.dispatchEvent(new Event('change', { bubbles: true }))
            })
        },
        popoverStyle() {
            const rect = this.$refs.trigger.getBoundingClientRect()
            const width = Math.min(328, window.innerWidth - 16)
            const height = 424
            const left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8))
            const top = rect.bottom + height <= window.innerHeight - 8 ? rect.bottom + 4 : Math.max(8, rect.top - height - 4)
            return `left:${left}px;top:${top}px;width:${width}px`
        },
    }"
    x-on:click.outside="close()"
    x-on:resize.window="close()"
    x-on:scroll.window="open = false"
>
    <label id="{{ $id }}-label" for="{{ $id }}">{{ $label }}</label>
    <button
        x-ref="trigger"
        class="date-picker-trigger"
        id="{{ $id }}"
        type="button"
        x-on:click="open ? close() : openCalendar()"
        x-on:keydown.arrow-down.prevent="openCalendar()"
        x-bind:aria-expanded="open"
        x-bind:data-empty="value === ''"
        aria-haspopup="dialog"
        aria-labelledby="{{ $id }}-label {{ $id }}-value"
        aria-invalid="{{ $invalid ? 'true' : 'false' }}"
        @if($describedby) aria-describedby="{{ $describedby }}" @endif
        @if($required) aria-required="true" @endif
    >
        <span id="{{ $id }}-value" x-text="display()"></span>
        <x-ui.icon name="calendar" />
    </button>
    <input x-ref="value" type="hidden" name="{{ $name }}" x-bind:value="value" {{ $attributes->whereStartsWith('wire:') }}>
    <div
        x-cloak
        x-show="open"
        x-bind:style="open ? popoverStyle() : ''"
        class="date-picker-popover"
        role="dialog"
        aria-modal="false"
        aria-labelledby="{{ $id }}-month"
        x-on:keydown.escape.prevent.stop="close(true)"
    >
        <div class="date-picker-head">
            <button type="button" x-on:click="moveMonth(-1)" x-bind:disabled="!canMoveMonth(-1)" aria-label="Bulan sebelumnya"><x-ui.icon name="chevron-left" /></button>
            <strong id="{{ $id }}-month" x-text="monthLabel()"></strong>
            <button type="button" x-on:click="moveMonth(1)" x-bind:disabled="!canMoveMonth(1)" aria-label="Bulan berikutnya"><x-ui.icon name="chevron-right" /></button>
        </div>
        <div class="date-picker-week" aria-hidden="true"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>
        <div class="date-picker-grid" role="grid" x-ref="calendar" aria-label="Kalender">
            <template x-for="(week, weekIndex) in weeks()" x-bind:key="weekIndex">
                <div class="date-picker-row" role="row">
                    <template x-for="day in week" x-bind:key="day.iso">
                        <button
                            type="button"
                            role="gridcell"
                            x-bind:data-date="day.iso"
                            x-bind:data-outside="!day.currentMonth"
                            x-bind:data-today="day.iso === today()"
                            x-bind:disabled="day.disabled"
                            x-bind:aria-label="day.label"
                            x-bind:aria-selected="day.iso === value"
                            x-bind:aria-current="day.iso === today() ? 'date' : null"
                            x-bind:tabindex="day.iso === focusDate ? 0 : -1"
                            x-on:click="select(day)"
                            x-on:focus="focusDate = day.iso"
                            x-on:keydown.arrow-left.prevent="moveDay(-1)"
                            x-on:keydown.arrow-right.prevent="moveDay(1)"
                            x-on:keydown.arrow-up.prevent="moveDay(-7)"
                            x-on:keydown.arrow-down.prevent="moveDay(7)"
                            x-on:keydown.page-up.prevent="moveMonth(-1)"
                            x-on:keydown.page-down.prevent="moveMonth(1)"
                            x-text="day.day"
                        ></button>
                    </template>
                </div>
            </template>
        </div>
        <div class="date-picker-foot"><button type="button" x-on:click="selectToday()">Hari ini</button><button type="button" x-on:click="close(true)">Tutup</button></div>
    </div>
</div>
