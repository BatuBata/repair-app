@props([
    'method',
    'bank',
    'prefix',
    'methodModel' => 'payment_method',
    'bankModel' => 'payment_bank',
])
<div class="payment-fields">
    <div class="field">
        <label>Metode pembayaran</label>
        <div class="segmented" role="group" aria-label="Metode pembayaran">
            @foreach(\App\Support\Payment::METHODS as $value => $label)
                <button type="button" wire:click="$set('{{ $methodModel }}', '{{ $value }}')" aria-pressed="{{ $method === $value ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        @if($errors->has($methodModel))<span class="error-text" id="{{ $prefix }}-method-error">{{ $errors->first($methodModel) }}</span>@endif
    </div>
    @if($method === 'transfer')
        <x-ui.search-select :name="$prefix.'-bank'" label="Bank tujuan" :options="\App\Support\Payment::bankOptions()" :value="$bank" placeholder="Cari dan pilih bank" wire:model="{{ $bankModel }}" :invalid="$errors->has($bankModel)" :describedby="$prefix.'-bank-error'" required />
        @if($errors->has($bankModel))<span class="error-text" id="{{ $prefix }}-bank-error">{{ $errors->first($bankModel) }}</span>@endif
    @endif
</div>
