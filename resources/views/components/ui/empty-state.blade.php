@props([
    'icon' => 'inbox',
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-10 text-center') }}>
    <span class="grid size-10 place-items-center rounded-full bg-slate-100 text-slate-400" aria-hidden="true">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
    </span>
    <p class="mt-3 text-sm font-medium text-slate-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
