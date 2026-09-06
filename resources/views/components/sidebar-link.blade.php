@props([
    'href',
    'active' => false,
])

<a
    href="{{ $href }}"
    @class([
        'sidebar-link flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition',
        'bg-slate-100 font-semibold text-slate-900 shadow-sm' => $active,
        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
    ])
>
    {{ $slot }}
</a>
