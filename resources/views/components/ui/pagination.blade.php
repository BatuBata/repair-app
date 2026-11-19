@php($isLivewire = $livewire ?? false)
<nav class="index-pagination" aria-label="Navigasi halaman tabel" @if($isLivewire) wire:key="pagination-{{ $paginator->getPageName() }}" @endif>
    <p class="index-page-summary" aria-live="polite">Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ number_format($paginator->total(), 0, ',', '.') }} hasil</p>
    @if($paginator->hasPages())
        <div class="index-page-controls">
            @if($paginator->onFirstPage())
                <span class="btn sm" aria-disabled="true" aria-label="Halaman sebelumnya"><x-ui.icon name="chevron-left" /></span>
            @elseif($isLivewire)
                <button class="btn sm" type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Halaman sebelumnya"><x-ui.icon name="chevron-left" /></button>
            @else
                <a class="btn sm" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"><x-ui.icon name="chevron-left" /></a>
            @endif
            @foreach($elements as $element)
                @if(is_string($element))
                    <span class="index-page-gap">{{ $element }}</span>
                @else
                    @foreach($element as $page => $url)
                        @if($page === $paginator->currentPage())
                            <span class="btn sm primary" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                        @elseif($isLivewire)
                            <button class="btn sm index-page-number" type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Halaman {{ $page }}">{{ $page }}</button>
                        @else
                            <a class="btn sm index-page-number" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if(!$paginator->hasMorePages())
                <span class="btn sm" aria-disabled="true" aria-label="Halaman berikutnya"><x-ui.icon name="chevron-right" /></span>
            @elseif($isLivewire)
                <button class="btn sm" type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Halaman berikutnya"><x-ui.icon name="chevron-right" /></button>
            @else
                <a class="btn sm" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"><x-ui.icon name="chevron-right" /></a>
            @endif
        </div>
    @endif
</nav>
