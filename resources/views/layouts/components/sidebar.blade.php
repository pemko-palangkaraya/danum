<aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">

    <div class="flex h-20 items-center border-b border-slate-100 px-6">
        <a href="{{ route('dashboard') }}">
            <x-danum-logo class="h-9 w-auto text-yellow-400" />
        </a>
    </div>

    <nav class="flex-1 px-4 py-6">

        <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Application
        </p>

        <a
            href="{{ route('dashboard') }}"
            @class([ 'flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition' , 'bg-slate-900 text-white shadow-sm'=> request()->routeIs('dashboard'),
            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('dashboard'),
            ])
            >
            <svg
                class="mr-3 h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z" />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 21v-6h6v6" />
            </svg>

            Dashboard
        </a>

    </nav>

    <div class="border-t border-slate-100 p-4">

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                <svg
                    class="mr-3 h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 3h4.5A1.5 1.5 0 0 1 21 4.5v15a1.5 1.5 0 0 1-1.5 1.5H15" />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10 8l4 4-4 4M14 12H3" />
                </svg>

                Logout
            </button>

        </form>

    </div>

</aside>