<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('landing.partials.head')
<body>
<div class="landing">
    @include('landing.partials.nav')

    <main>
        @include('landing.partials.hero')
        @include('landing.partials.features')
    </main>

    @include('landing.partials.footer')
</div>
</body>
</html>
