@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'flush' => false,
])

<section {{ $attributes->class('panel flex flex-col') }}>
    @if ($title || isset($actions))
        <header class="flex min-h-11 items-center justify-between gap-3 border-b border-line px-4 py-2">
            <div class="flex min-w-0 items-center gap-2">
                @if ($icon)
                    <x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0 text-slate-400" aria-hidden="true" />
                @endif
                <div class="min-w-0">
                    <h2 class="truncate text-[13px] font-semibold text-slate-900">{{ $title }}</h2>
                    @if ($description)
                        <p class="truncate text-xs text-slate-500">{{ $description }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['flex-1', 'p-4' => ! $flush])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-line px-4 py-2 text-xs text-slate-500">{{ $footer }}</footer>
    @endisset
</section>
