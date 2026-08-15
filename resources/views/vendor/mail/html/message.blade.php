<x-mail::layout>
{{-- Header --}}
@php
    // Embedded as a base64 data URI rather than linked via asset() — a
    // linked URL resolves from APP_URL, which for local/dev environments
    // (or any environment external recipients can't reach) means the logo
    // never loads for anyone reading the email outside this network.
    // Embedding the bytes directly in the HTML works everywhere, always.
    $tanakiHubLogoDataUri = 'data:image/png;base64,' . base64_encode(
        file_get_contents(public_path('images/brand/tanaki-hub-logo-dark.png'))
    );
@endphp
<x-slot:header>
<x-mail::header :url="config('app.url')">
<img src="{{ $tanakiHubLogoDataUri }}" class="brand-logo" alt="Tanaki Hub">
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} Tanaki Hub. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
