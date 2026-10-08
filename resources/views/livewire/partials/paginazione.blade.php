{{-- Paginazione condivisa (trait App\Livewire\Concerns\ConPaginazione). --}}
@php $pagina = $paginator->getPageName(); @endphp

<div>
    @if ($paginator->total() > 0)
        <nav aria-label="Paginazione" class="flex items-center justify-between gap-4 border-t border-line px-4 py-2 text-xs text-slate-500">
            <p class="tabular">
                Righe <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                di <span class="font-medium text-slate-700">{{ Number::format($paginator->total()) }}</span>
            </p>

            @if ($paginator->hasPages())
                <ul class="flex items-center gap-1">
                    <li>
                        <button type="button" wire:click="previousPage('{{ $pagina }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage())
                            class="grid size-7 place-items-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                            aria-label="Pagina precedente">
                            <x-lucide-chevron-left class="size-4" aria-hidden="true" />
                        </button>
                    </li>

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="px-1 text-slate-400" aria-hidden="true">{{ $element }}</li>
                        @else
                            @foreach ($element as $numero => $url)
                                <li wire:key="paginator-{{ $pagina }}-{{ $numero }}">
                                    @if ($numero == $paginator->currentPage())
                                        <span class="tabular grid h-7 min-w-7 place-items-center rounded-md bg-primary-600 px-2 font-medium text-white" aria-current="page">{{ $numero }}</span>
                                    @else
                                        <button type="button" wire:click="gotoPage({{ $numero }}, '{{ $pagina }}')" wire:loading.attr="disabled"
                                            class="tabular grid h-7 min-w-7 place-items-center rounded-md px-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                                            aria-label="Pagina {{ $numero }}">{{ $numero }}</button>
                                    @endif
                                </li>
                            @endforeach
                        @endif
                    @endforeach

                    <li>
                        <button type="button" wire:click="nextPage('{{ $pagina }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages())
                            class="grid size-7 place-items-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                            aria-label="Pagina successiva">
                            <x-lucide-chevron-right class="size-4" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            @endif
        </nav>
    @endif
</div>
