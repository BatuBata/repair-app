<div class="expenses-page">
    <style>
        .expenses-page { overflow-wrap:anywhere; }
        .expenses-page .expense-filters,.expenses-page .expense-form { display:grid; gap:16px; max-width:760px; }
        .expenses-page .expense-filters { grid-template-columns:repeat(2,minmax(0,1fr)); margin:20px 0 24px; }
        .expenses-page .expense-form { gap:20px; }
        .expenses-page .expense-list .act { width:48px; }
        .expenses-page .expense-list .expense-name { display:block; font-weight:500; }
        .expenses-page .expense-list .expense-meta { display:block; }
        .expenses-page .expense-list .expense-meta { color:var(--muted); font-size:var(--fs-small); }
        .expenses-page .expense-total { margin:16px 0; display:flex; justify-content:flex-end; gap:24px; font-variant-numeric:tabular-nums; }
        .expenses-page .form-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .expenses-page .expense-form .money { font-size:16px; }
        .expenses-page dialog .dialog-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:8px; }
        @media(max-width:1023.98px) {
            .expenses-page .expense-list thead { display:none; }
            .expenses-page .expense-list tbody tr { display:grid; grid-template-columns:48px minmax(0,1fr) auto; gap:4px 8px; padding:10px 0; border-bottom:1px solid var(--rule); }
            .expenses-page .expense-list tbody td { border:0; padding:0; }
            .expenses-page .expense-list tbody td:nth-child(2) { grid-column:2; }
            .expenses-page .expense-list tbody td:nth-child(3) { grid-column:3; grid-row:1; }
            .expenses-page .expense-list .expense-meta { display:block; }
            .expenses-page .expense-list .empty-cell { grid-column:1/-1; }
            .expenses-page .expense-filters { margin:16px 0; }
        .expenses-page:has(.actionbar) { padding-bottom:84px; }
        }
        @media(max-width:639.98px) {
            .expenses-page .expense-filters { grid-template-columns:1fr 1fr; }
        }
    </style>
    <div class="title-row"><div><h1>{{ $mode === 'list' ? 'Pengeluaran' : ($mode === 'create' ? 'Tambah pengeluaran' : 'Ubah pengeluaran') }}</h1><p class="sub">Catat biaya harian toko</p></div>
        @if($mode === 'list')<button class="btn primary desktop-only" type="button" wire:click="create">Tambah pengeluaran</button>@endif
    </div>
    @if(session('expense-status'))<p class="notice" role="status">{{ session('expense-status') }}</p>@endif
    @if($mode !== 'list')
        @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
        <form class="expense-form" wire:submit="save" novalidate x-init="$nextTick(() => $el.querySelector('[aria-invalid=true]')?.focus())">
            <label class="field"><span>Tanggal</span><input class="input" type="date" wire:model="spent_on" required aria-invalid="{{ $errors->has('spent_on') ? 'true' : 'false' }}" aria-describedby="spent_on-error">@error('spent_on')<span class="error-text" id="spent_on-error">{{ $message }}</span>@enderror</label>
            <label class="field"><span>Kategori</span><select class="input" wire:model="category" required aria-invalid="{{ $errors->has('category') ? 'true' : 'false' }}" aria-describedby="category-error"><option value="">Pilih kategori</option>@foreach($categories as $item)<option value="{{ $item->value }}">{{ ucfirst($item->value) }}</option>@endforeach</select>@error('category')<span class="error-text" id="category-error">{{ $message }}</span>@enderror</label>
            <label class="field"><span>Keterangan <span class="sub">(opsional)</span></span><input class="input" type="text" maxlength="255" wire:model="description" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" aria-describedby="description-error">@error('description')<span class="error-text" id="description-error">{{ $message }}</span>@enderror</label>
            <div><x-ui.money name="amount" label="Nominal" :value="$amount" x-on:input="$wire.amount = $el.value.replace(/\D/g, '')" required aria-invalid="{{ $errors->has('amount') ? 'true' : 'false' }}" aria-describedby="amount-error" />@error('amount')<p class="error-text" id="amount-error">{{ $message }}</p>@enderror</div>
            <div class="form-actions"><button class="btn ghost" type="button" wire:click="cancel">Batal</button><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</button></div>
            <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        </form>
    @else
        <div class="expense-filters"><label class="field"><span>Dari tanggal</span><input class="input" type="date" wire:model.live="from" aria-invalid="{{ $filterErrors->has('from') ? 'true' : 'false' }}" aria-describedby="from-error">@if($filterErrors->has('from'))<span class="error-text" id="from-error" role="alert">{{ $filterErrors->first('from') }}</span>@endif</label><label class="field"><span>Sampai tanggal</span><input class="input" type="date" wire:model.live="to" aria-invalid="{{ $filterErrors->has('to') ? 'true' : 'false' }}" aria-describedby="to-error">@if($filterErrors->has('to'))<span class="error-text" id="to-error" role="alert">{{ $filterErrors->first('to') }}</span>@endif</label></div>
        <table class="table expense-list"><thead><tr><th class="act">Aksi</th><th>Tanggal · kategori · keterangan</th><th class="n">Nominal</th></tr></thead><tbody>
            @forelse($expenses as $expense)
                <tr wire:key="expense-{{ $expense->id }}"><td class="act"><x-ui.row-actions :id="'expense-actions-'.$expense->id" :label="$expense->category->value.' '.$expense->spent_on->format('d/m/Y')"><button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).edit({{ $expense->id }})"><x-ui.icon name="pencil" />Ubah</button><button class="danger" type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).confirmDelete({{ $expense->id }})"><x-ui.icon name="trash-2" />Hapus</button></x-ui.row-actions></td><td><span class="expense-name">{{ $expense->spent_on->format('d/m/Y') }} · {{ ucfirst($expense->category->value) }}</span><span class="expense-meta">{{ $expense->description ?: 'Tanpa keterangan' }}</span></td><td class="n">Rp{{ number_format($expense->amount, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" class="empty-cell"><div class="empty"><strong>Belum ada pengeluaran pada rentang ini.</strong><p>Ubah tanggal atau catat pengeluaran baru.</p></div></td></tr>
            @endforelse
        </tbody></table>
        <div class="expense-total"><span>Total pengeluaran</span><strong>Rp{{ number_format($total, 0, ',', '.') }}</strong></div>
        <div class="actionbar"><button class="btn primary" type="button" wire:click="create">Tambah pengeluaran</button></div>
        @if($deleteExpense)<dialog class="sheet-dialog" x-init="$el.showModal()" x-on:cancel.prevent="$el.close(); $wire.cancelDelete().then(() => document.querySelector('[aria-controls=\'expense-actions-{{ $deleteExpense->id }}\']')?.focus())" aria-labelledby="delete-expense-title"><div class="dialog-body"><h2 id="delete-expense-title">Hapus pengeluaran?</h2><p>{{ ucfirst($deleteExpense->category->value) }} · {{ $deleteExpense->spent_on->format('d/m/Y') }} · Rp{{ number_format($deleteExpense->amount, 0, ',', '.') }} akan dihapus. Tindakan ini tidak dapat dibatalkan.</p><div class="dialog-actions"><button class="btn ghost" type="button" x-on:click="$el.closest('dialog').close(); $wire.cancelDelete().then(() => document.querySelector('[aria-controls=\'expense-actions-{{ $deleteExpense->id }}\']')?.focus())">Batal</button><button class="btn danger" type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Ya, hapus</button></div></div></dialog>@endif
    @endif
</div>
