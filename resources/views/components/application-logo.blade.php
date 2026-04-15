@php
    $nisLogoPublicPath = public_path('images/nis-logo.png');
@endphp

@if (file_exists($nisLogoPublicPath))
    <img
        src="{{ asset('images/nis-logo.png') }}"
        alt="Nigeria Immigration Service"
        {{ $attributes->merge(['class' => 'h-10 w-auto']) }}
    />
@else
    <div {{ $attributes->merge(['class' => 'text-emerald-700 font-bold text-xl']) }}>
        NIS
    </div>
@endif
