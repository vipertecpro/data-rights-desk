@extends('data-rights::layouts.desk')

@section('title', 'Notices')

@section('content')
    <div class="drd-pagehead">
        <div>
            <h1>Notices</h1>
            <p class="drd-lead">The notice is built from your purposes and published in every offered language (s.5, Rule 3). Every version is kept.</p>
        </div>
        <form method="POST" action="{{ route('data-rights-desk.notices.publish') }}">
            @csrf
            <button type="submit" @class(['drd-button', 'drd-button--primary' => $hasChanges]) @disabled($problems !== [])>Publish a new version</button>
        </form>
    </div>

    @if ($problems !== [])
        <div class="drd-alert drd-alert--danger" role="alert">
            <p>The notice cannot be published until these are fixed:</p>
            <ul>
                @foreach ($problems as $problem)
                    <li>{{ $problem }}</li>
                @endforeach
            </ul>
        </div>
    @elseif (! $hasChanges)
        <p class="drd-alert drd-alert--ok">The published notice matches the current config.</p>
    @endif

    <table class="drd-table">
        <thead>
            <tr><th scope="col">Language</th><th scope="col">Text status</th><th scope="col">Published</th><th scope="col">Up to date</th></tr>
        </thead>
        <tbody>
            @foreach ($locales as $locale)
                <tr>
                    <td data-label="Language">{{ $locale['name'] }} <span class="drd-muted drd-small">{{ $locale['code'] }}</span></td>
                    <td data-label="Text status"><span @class(['drd-badge', 'drd-badge--ok' => $locale['status'] === 'ready', 'drd-badge--warn' => $locale['status'] !== 'ready'])>{{ str_replace('_', ' ', $locale['status']) }}</span></td>
                    <td data-label="Published">
                        @if ($locale['current'])
                            <a href="{{ route('data-rights-desk.notices.show', $locale['current']) }}">Version {{ $locale['current']->version }}</a>
                        @else
                            <span class="drd-danger">Not published</span>
                        @endif
                    </td>
                    <td data-label="Up to date">
                        @if ($locale['stale'])
                            <span class="drd-badge drd-badge--danger">Needs publishing</span>
                        @else
                            <span class="drd-badge drd-badge--ok">Yes</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>All versions</h2>
    @forelse ($versions as $version => $notices)
        <p>
            Version {{ $version }}, {{ \Vipertecpro\DataRightsDesk\Support\Dates::time($notices->first()->published_at) }}:
            @foreach ($notices as $notice)
                <a href="{{ route('data-rights-desk.notices.show', $notice) }}">{{ $notice->locale }}</a>@if (! $loop->last), @endif
            @endforeach
        </p>
    @empty
        <p class="drd-muted">No notice published yet. Publishing also works with php artisan data-rights:notice.</p>
    @endforelse
@endsection
