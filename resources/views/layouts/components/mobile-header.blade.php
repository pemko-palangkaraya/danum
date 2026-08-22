<header class="sticky top-0 z-30 border-b border-slate-200 bg-white lg:hidden">

    <div class="flex h-16 items-center justify-between px-4">

        {{-- Logo --}}
        <a
            href="{{ route('dashboard') }}"
            class="inline-flex items-center">
            <x-danum-logo class="h-8 w-auto text-yellow-400" />
        </a>

        {{-- Mobile Menu --}}
        <details class="relative">

            <summary
                class="flex cursor-pointer list-none items-center rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100"
                aria-label="Buka menu navigasi">
                <svg
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path
                        stroke-linecap="round"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </summary>

            <div
                class="absolute right-0 mt-2 w-48 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">

                <p class="px-3 pb-2 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Application
                </p>

                {{-- Dashboard --}}
                <a
                    href="{{ route('dashboard') }}"
                    @class([ 'flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition' , 'bg-slate-900 text-white'=> request()->routeIs('dashboard'),
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

                {{-- Tenants --}}
                <a
                    href="{{ route('tenants.index') }}"
                    @class([ 'flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition' , 'bg-slate-900 text-white'=> request()->routeIs('tenants.*'),
                    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('tenants.*'),
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
                            d="M3 21h18" />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 21V7l7-4 7 4v14" />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 21v-4h6v4" />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 10h.01M15 10h.01M9 13h.01M15 13h.01" />
                    </svg>

                    Tenants
                </a>

                <div class="my-2 border-t border-slate-100"></div>

                {{-- Logout --}}
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
        </details>

    </div>

</header>