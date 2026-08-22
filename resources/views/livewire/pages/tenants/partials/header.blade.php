<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            Tenant Management
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Kelola organisasi yang menggunakan DANUM.
        </p>
    </div>

    <a
        href="{{ route('tenants.create') }}"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="h-4 w-4">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 5v14" />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 12h14" />
        </svg>

        Add Tenant
    </a>

</div>