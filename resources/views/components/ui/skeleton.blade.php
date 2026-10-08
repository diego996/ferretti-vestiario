{{-- Righe segnaposto durante il caricamento. --}}
@props(['rows' => 4])

<div {{ $attributes->class('animate-pulse space-y-2.5 p-4') }} aria-hidden="true">
    @for ($i = 0; $i < $rows; $i++)
        <div class="flex items-center gap-3">
            <div class="h-3 w-1/4 rounded bg-slate-200"></div>
            <div class="h-3 flex-1 rounded bg-slate-100"></div>
            <div class="h-3 w-12 rounded bg-slate-200"></div>
        </div>
    @endfor
</div>
