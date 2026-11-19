<div>
    <div class="title-row"><div><h1>{{ $editing ? ($customerId ? 'Ubah pelanggan' : 'Tambah pelanggan') : 'Pelanggan' }}</h1><p class="sub">Nama, nomor HP, dan alamat pelanggan toko.</p></div>
        @unless($editing)<button class="btn primary" type="button" wire:click="create">Tambah pelanggan</button>@endunless
    </div>
    @if(session('customer_status'))<x-ui.notice type="success">{{ session('customer_status') }}</x-ui.notice>@endif
    @if($editing)
        <form class="form" wire:submit="save" style="max-width:560px" novalidate>
            <div class="field"><label for="name">Nama pelanggan</label><input id="name" class="input" wire:model="name" style="text-transform:capitalize" maxlength="100" required placeholder="mis. Bu Ratna Sari" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error">@error('name')<p id="name-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="customer_phone">Nomor HP / WhatsApp</label><input id="customer_phone" class="input" type="tel" wire:model="customer_phone" maxlength="40" required placeholder="0812 3456 7890" aria-invalid="{{ $errors->has('customer_phone') ? 'true' : 'false' }}" aria-describedby="phone-error">@error('customer_phone')<p id="phone-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="address">Alamat <span class="opt">opsional</span></label><textarea id="address" class="textarea" wire:model="address" maxlength="255" placeholder="mis. Jl. A. Yani, Samarinda" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" aria-describedby="address-error"></textarea>@error('address')<p id="address-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div><button class="btn ghost" type="button" wire:click="cancel">Batal</button><button class="btn primary" type="submit" wire:loading.attr="disabled">Simpan pelanggan</button></div>
        </form>
    @else
        <x-ui.index-search label="Cari pelanggan" placeholder="Cari nama atau nomor HP" :total="$customers->total()" unit="pelanggan" wire:model.live.debounce.300ms="search" />
        <table class="table"><thead><tr><th class="act">Aksi</th><th>Nama</th><th>Nomor HP</th><th>Alamat</th></tr></thead><tbody>
            @forelse($customers as $customer)
                <tr wire:key="customer-{{ $customer->id }}"><td class="act"><x-ui.row-actions :id="'customer-actions-'.$customer->id" :label="ucwords($customer->name)">
                    <a role="menuitem" href="{{ route('customers.show', $customer->id) }}"><x-ui.icon name="eye" />Lihat pelanggan</a>
                    <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).edit({{ $customer->id }})"><x-ui.icon name="pencil" />Edit pelanggan</button>
                    <button type="button" class="danger" role="menuitem" onclick="if (confirm(@js('Hapus pelanggan '.ucwords($customer->name).' dari buku alamat? TTS dan nota tetap tersimpan.'))) Livewire.find(@js($this->getId())).delete({{ $customer->id }})"><x-ui.icon name="trash-2" />Hapus pelanggan</button>
                </x-ui.row-actions></td><td class="c-who">{{ ucwords($customer->name) }}</td><td class="c-meta">{{ $customer->phone }}</td><td class="c-meta">{{ $customer->address ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="4"><div class="empty">Belum ada pelanggan yang cocok.</div></td></tr>
            @endforelse
        </tbody></table>
    {{ $customers->onEachSide(1)->links('components.ui.pagination', ['livewire' => true]) }}
    @endif
</div>
