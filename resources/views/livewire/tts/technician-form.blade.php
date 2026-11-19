<form id="assign-technician" class="form" wire:submit="assign">
    <x-ui.search-select name="technician_id" :label="$order->technician_id ? 'Ganti teknisi' : 'Pilih teknisi'" :options="$technicians->map(fn ($technician) => ['value' => $technician->id, 'label' => $technician->name])" :value="$technician_id" placeholder="Cari teknisi aktif" wire:model="technician_id" :invalid="$errors->has('technician_id')" />
    @error('technician_id')<p class="error-text" role="alert">{{ $message }}</p>@enderror
    @if($stage === 'diterima')
        <fieldset class="assignment-choices"><legend>Setelah teknisi ditetapkan</legend>
            <label class="check"><input type="radio" wire:model="assignment_stage" value="pengecekan"><span>Pengecekan dulu<small>Periksa dan tawarkan tindakan ke pelanggan.</small></span></label>
            <label class="check"><input type="radio" wire:model="assignment_stage" value="dikerjakan"><span>Langsung dikerjakan<small>Pelanggan sudah menyetujui perbaikan.</small></span></label>
        </fieldset>
    @else<button class="btn" type="submit" wire:loading.attr="disabled" wire:target="assign">Simpan teknisi</button>@endif
</form>
