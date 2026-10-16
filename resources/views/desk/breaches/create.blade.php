@extends('data-rights::layouts.desk')

@section('title', 'Record a breach')

@section('content')
    <p><a href="{{ route('data-rights-desk.breaches.index') }}">Back to breaches</a></p>
    <h1>Record a breach</h1>
    <p class="drd-lead">Write what you know now; you can add more later. Drafts for the Board and for people are built from these fields.</p>

    <form method="POST" action="{{ route('data-rights-desk.breaches.store') }}" class="drd-card">
        @csrf
        @include('data-rights::desk.breaches.fields')
        <button type="submit" class="drd-button drd-button--primary">Record breach</button>
    </form>
@endsection
