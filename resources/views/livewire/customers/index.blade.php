<div>
    <div class="title-row"><div><h1>{{ $editing ? ($customerId ? 'Ubah pelanggan' : 'Tambah pelanggan') : 'Pelanggan' }}</h1><p class="sub">Nama, nomor HP, dan alamat pelanggan toko.</p></div>
        @unless($editing)<button class="btn primary" type="button" wire:click="create">Tambah pelanggan</button>@endunless
    </div>
    @if(session('customer_status'))<p class="notice" role="status">{{ session('customer_status') }}</p>@endif
    @if($editing)
        <form class="form" wire:submit="save" style="max-width:560px" novalidate>
            <div class="field"><label for="name">Nama pelanggan</label><input id="name" class="input" wire:model="name" maxlength="100" required placeholder="mis. Bu Ratna Sari" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error">@error('name')<p id="name-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="customer_phone">Nomor HP / WhatsApp</label><input id="customer_phone" class="input" type="tel" wire:model="customer_phone" maxlength="40" required placeholder="0812 3456 7890" aria-invalid="{{ $errors->has('customer_phone') ? 'true' : 'false' }}" aria-describedby="phone-error">@error('customer_phone')<p id="phone-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="address">Alamat <span class="opt">opsional</span></label><textarea id="address" class="textarea" wire:model="address" maxlength="255" placeholder="mis. Jl. A. Yani, Samarinda" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" aria-describedby="address-error"></textarea>@error('address')<p id="address-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div><button class="btn ghost" type="button" wire:click="cancel">Batal</button><button class="btn primary" type="submit" wire:loading.attr="disabled">Simpan pelanggan</button></div>
        </form>
    @else
        <div class="toolbar"><label class="field"><span class="sr-only">Cari pelanggan</span><input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau nomor HP"></label></div>
        <table class="table"><thead><tr><th class="act">Aksi</th><th>Nama</th><th>Nomor HP</th><th>Alamat</th></tr></thead><tbody>
            @forelse($customers as $customer)
                <tr wire:key="customer-{{ $customer->id }}"><td class="act"><button class="btn" type="button" wire:click="edit({{ $customer->id }})" aria-label="Ubah pelanggan {{ $customer->name }}">Ubah</button></td><td class="c-who">{{ $customer->name }}</td><td class="c-meta">{{ $customer->phone }}</td><td class="c-meta">{{ $customer->address ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="4"><div class="empty">Belum ada pelanggan yang cocok.</div></td></tr>
            @endforelse
        </tbody></table>
        {{ $customers->links() }}
    @endif
</div>
