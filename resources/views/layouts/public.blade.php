<!DOCTYPE html>
<html lang="{{ $drd['locale'] }}" dir="{{ $drd['dir'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $drd['business'] }}</title>
    @if ($drd['css'])
        <link rel="stylesheet" href="{{ $drd['css'] }}">
    @endif
</head>
<body class="drd">
    <a class="drd-skip" href="#drd-main">{{ __('data-rights::public.layout.skip') }}</a>

    <header class="drd-header">
        <div class="drd-wrap drd-wrap--narrow">
            <span class="drd-brand">{{ $drd['business'] }}</span>
            <nav aria-label="{{ __('data-rights::public.layout.nav_label') }}">
                <ul class="drd-nav">
                    @foreach ([
                        'data-rights.index' => __('data-rights::public.nav.rights'),
                        'data-rights.notice' => __('data-rights::public.nav.notice'),
                        'data-rights.preferences' => __('data-rights::public.nav.preferences'),
                        'data-rights.requests.index' => __('data-rights::public.nav.requests'),
                    ] as $routeName => $label)
                        <li>
                            <a href="{{ route($routeName, $lang ?? []) }}" @if (request()->routeIs($routeName, $routeName.'.*')) aria-current="page" @endif>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </header>

    <main id="drd-main" class="drd-wrap drd-wrap--narrow drd-main">
        @include('data-rights::partials.flash')
        @yield('content')
    </main>

    <footer class="drd-footer">
        <div class="drd-wrap drd-wrap--narrow">
            @include('data-rights::partials.languages')
        </div>
    </footer>
</body>
</html>
