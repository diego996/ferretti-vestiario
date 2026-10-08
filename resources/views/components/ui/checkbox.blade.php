{{-- Casella di spunta con etichetta inline. --}}
<label class="flex w-fit cursor-pointer items-center gap-2 text-[13px] text-slate-700">
    <input type="checkbox" {{ $attributes->class('size-4 rounded border-line-strong accent-primary-600') }}>
    {{ $slot }}
</label>
