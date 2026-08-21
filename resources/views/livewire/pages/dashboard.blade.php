<div>

    {{-- Page Header --}}
    <div class="mb-8">

        <p class="text-sm font-medium text-slate-400">
            Application
        </p>

        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">
            Dashboard
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            Selamat datang kembali di DANUM.
        </p>

    </div>


    {{-- Welcome Card --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="p-6 sm:p-8">

            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <p class="text-sm font-medium text-slate-400">
                        Welcome back
                    </p>

                    <h2 class="mt-1 text-xl font-semibold text-slate-900">
                        {{ auth()->user()->name }}
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Anda berhasil masuk ke sistem DANUM.
                    </p>

                </div>

                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-lg font-semibold text-slate-700">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>

            </div>

        </div>

    </div>

</div>