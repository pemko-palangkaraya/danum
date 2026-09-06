<aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
    <div class="flex h-20 items-center border-b border-slate-100 px-6">
        <a href="{{ route('dashboard') }}"><x-danum-logo class="h-9 w-auto text-yellow-400" /></a>
    </div>

    <style>
        /* Keep active navigation consistent across every sidebar entry. */
        aside nav > div > button {
            position: relative;
            border: 1px solid transparent;
            background: transparent;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        aside nav > div > button::before {
            content: '';
            position: absolute;
            left: -1px;
            top: 7px;
            bottom: 7px;
            width: 3px;
            border-radius: 9999px;
            background: rgb(226 232 240);
            transition: background-color .15s ease;
        }

        aside nav > div > button:hover,
        aside nav > div > button[aria-expanded="true"] {
            border-color: rgb(226 232 240);
            background: rgb(241 245 249);
            color: rgb(15 23 42);
        }

        aside nav > div > button[aria-expanded="true"]::before {
            background: rgb(15 23 42);
        }

        aside nav a,
        aside > div a {
            transition: background-color .15s ease, border-color .15s ease, color .15s ease, padding-left .15s ease;
        }

        aside nav a:hover,
        aside > div a:hover {
            background: rgb(241 245 249);
            color: rgb(15 23 42);
        }

        aside nav a[href="{{ url()->current() }}"],
        aside nav a[aria-current="page"],
        aside > div a[href="{{ url()->current() }}"],
        aside > div a[aria-current="page"] {
            background: rgb(241 245 249) !important;
            color: rgb(15 23 42) !important;
            font-weight: 600;
            box-shadow: inset 3px 0 0 rgb(15 23 42);
        }

        aside nav > div > div {
            margin-left: .25rem;
            padding: .25rem 0 .25rem .5rem;
            border-left: 1px solid rgb(226 232 240);
        }
    </style>

    @include('layouts.components.sidebar-navigation')
    @include('layouts.components.sidebar-footer')
</aside>
