<aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
    <div class="flex h-20 items-center border-b border-slate-100 px-6">
        <a href="{{ route('dashboard') }}"><x-danum-logo class="h-9 w-auto text-yellow-400" /></a>
    </div>

    @include('layouts.components.sidebar-navigation')
    @include('layouts.components.sidebar-footer')
</aside>
