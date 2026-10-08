{{--
    Notifiche toast globali.
    - Da Livewire:   $this->dispatch('toast', type: 'success', message: '...')
    - Da redirect:   session()->flash('toast', ['type' => 'success', 'message' => '...'])
--}}
<div
    x-data="{
        items: [],
        push(detail) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type: detail.type ?? 'success', message: detail.message });
            setTimeout(() => this.remove(id), detail.type === 'error' ? 8000 : 4500);
        },
        remove(id) { this.items = this.items.filter(i => i.id !== id) },
    }"
    x-init="@if (session('toast')) push(@js(session('toast'))) @endif"
    x-on:toast.window="push($event.detail)"
    class="pointer-events-none fixed right-4 bottom-4 z-50 flex w-80 flex-col gap-2"
    role="status"
    aria-live="polite"
>
    <template x-for="item in items" :key="item.id">
        <div
            x-transition:enter="transition duration-150 ease-out"
            x-transition:enter-start="translate-y-1 opacity-0"
            x-transition:leave="transition duration-100 ease-in"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex items-start gap-2.5 rounded-panel border bg-white px-3 py-2.5 text-[13px] shadow-pop"
            :class="{
                'border-emerald-200': item.type === 'success',
                'border-red-200': item.type === 'error',
                'border-amber-200': item.type === 'warning',
            }"
        >
            <span class="mt-0.5 shrink-0">
                <template x-if="item.type === 'success'"><x-lucide-circle-check class="size-4 text-emerald-600" /></template>
                <template x-if="item.type === 'error'"><x-lucide-circle-x class="size-4 text-red-600" /></template>
                <template x-if="item.type === 'warning'"><x-lucide-triangle-alert class="size-4 text-amber-600" /></template>
            </span>
            <p class="flex-1 text-slate-800" x-text="item.message"></p>
            <button type="button" class="shrink-0 rounded text-slate-400 hover:text-slate-700" x-on:click="remove(item.id)" aria-label="Chiudi notifica">
                <x-lucide-x class="size-4" />
            </button>
        </div>
    </template>
</div>
