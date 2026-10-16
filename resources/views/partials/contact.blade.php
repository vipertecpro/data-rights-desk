{{-- Rule 9: the business contact block. --}}
<address class="drd-card drd-address">
    @foreach (\Vipertecpro\DataRightsDesk\Support\Contact::lines(app()->getLocale()) as $drdLine)
        {{ $drdLine }}@if (! $loop->last)<br>@endif
    @endforeach
</address>
