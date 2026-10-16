<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · Data Rights Desk</title>
    @if ($drd['css'])
        <link rel="stylesheet" href="{{ $drd['css'] }}">
    @endif
</head>
<body class="drd">
    <a class="drd-skip" href="#drd-main">Skip to content</a>

    <header class="drd-header">
        <div class="drd-wrap">
            <a class="drd-brand" href="{{ route('data-rights-desk.index') }}">Data Rights Desk<small>{{ $drd['business'] }}</small></a>
        </div>
    </header>

    <nav class="drd-tabs" aria-label="Dashboard">
        <div class="drd-wrap">
            <ul class="drd-nav">
                @foreach ([
                    ['data-rights-desk.index', 'data-rights-desk.index', 'Overview'],
                    ['data-rights-desk.requests.index', 'data-rights-desk.requests.*', 'Requests'],
                    ['data-rights-desk.consents.index', 'data-rights-desk.consents.*', 'Consents'],
                    ['data-rights-desk.notices.index', 'data-rights-desk.notices.*', 'Notices'],
                    ['data-rights-desk.breaches.index', 'data-rights-desk.breaches.*', 'Breaches'],
                    ['data-rights-desk.retention.index', 'data-rights-desk.retention.*', 'Retention'],
                    ['data-rights-desk.processors.index', 'data-rights-desk.processors.*', 'Processors'],
                    ['data-rights-desk.children', 'data-rights-desk.children', 'Children'],
                    ['data-rights-desk.check', 'data-rights-desk.check', 'Check'],
                ] as [$routeName, $pattern, $label])
                    <li><a href="{{ route($routeName) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
    </nav>

    <main id="drd-main" class="drd-wrap drd-main">
        @include('data-rights::partials.flash')
        @yield('content')
    </main>

    <footer class="drd-footer">
        <div class="drd-wrap">
            Data Rights Desk helps you meet the Digital Personal Data Protection Act, 2023 and the DPDP Rules, 2025. It is not legal advice.
        </div>
    </footer>
</body>
</html>
