@extends('data-rights::layouts.desk')

@section('title', 'Processors')

@section('content')
    <div class="drd-pagehead">
        <div>
            <h1>Processors</h1>
            <p class="drd-lead">Data Processors under contract (s.8(2), Rule 6(1)(f)) and other Data Fiduciaries you share data with. They appear in access summaries for the matching purposes (s.11(1)(b)).</p>
        </div>
        <a class="drd-button drd-button--primary" href="{{ route('data-rights-desk.processors.create') }}">Add processor</a>
    </div>

    @if ($processors->isEmpty())
        <p class="drd-muted">No processors recorded.</p>
    @else
        <table class="drd-table">
            <thead>
                <tr><th scope="col">Name</th><th scope="col">Purposes</th><th scope="col">Contract</th><th scope="col">State</th></tr>
            </thead>
            <tbody>
                @foreach ($processors as $processor)
                    <tr>
                        <td data-label="Name"><a href="{{ route('data-rights-desk.processors.edit', $processor) }}">{{ $processor->name }}</a><span class="drd-help">{{ $processor->kind->label() }}{{ $processor->country ? ', '.$processor->country : '' }}</span></td>
                        <td data-label="Purposes">{{ implode(', ', $processor->purposes) ?: 'None' }}</td>
                        <td data-label="Contract">
                            {{ $processor->contract_reference ?: 'No reference' }}
                            @if ($processor->security_clause)
                                <span class="drd-badge drd-badge--ok">Security clause</span>
                            @else
                                <span class="drd-badge drd-badge--warn">No security clause</span>
                            @endif
                        </td>
                        <td data-label="State">
                            {{ $processor->active ? 'Active' : 'Inactive' }}
                            @if ($processor->hidden_from_access_summary)
                                <span class="drd-help">Left out of access summaries (s.11(2))</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
