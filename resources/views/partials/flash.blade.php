@if (session('data-rights.status'))
    <div class="drd-alert drd-alert--ok" role="status">{{ session('data-rights.status') }}</div>
@endif

@if (isset($errors) && $errors->any())
    <div class="drd-alert drd-alert--danger" role="alert">
        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
