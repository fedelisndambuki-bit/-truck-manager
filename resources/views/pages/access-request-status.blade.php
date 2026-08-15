<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="color-scheme" content="light" />
    <title>{{ $heading }} — Tanaki Hub</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/tanaki-hub-icon-light.png') }}">
    @vite(['resources/css/auth.css'])
</head>
<body class="antialiased">
    <div class="tk-split min-h-screen w-full">
        <div class="tk-hero relative flex flex-col overflow-y-auto pb-10 pl-6 pr-6 pt-10 text-white lg:pb-12 lg:pl-20 lg:pr-16 lg:pt-[70px]">
            <x-auth.logo layout="horizontal" :mark-size="44" />
            <div class="mt-8 max-w-[540px]">
                <h1 class="text-[52px] font-extrabold leading-[1.12] tracking-tight">
                    One Platform.<br>
                    <span class="text-tanaki-teal">Every Operation.</span>
                </h1>
            </div>
        </div>

        <div class="flex flex-col bg-tanaki-bg-right lg:h-screen">
            <div class="flex flex-1 items-center justify-center overflow-y-auto px-6 py-8 lg:px-8 lg:py-8">
                <div class="tk-auth-card my-auto flex w-full max-w-[480px] flex-col gap-5 rounded-[20px] bg-white px-10 py-9 text-center">
                    <x-auth.logo layout="stacked" :mark-size="58" />

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $isError ? 'bg-red-50' : 'bg-tanaki-teal/10' }}">
                        @if ($isError)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7 text-red-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7 text-tanaki-teal">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @endif
                    </div>

                    <h2 class="text-[22px] font-bold leading-tight text-tanaki-ink">{{ $heading }}</h2>
                    <p class="text-sm text-tanaki-grey">{{ $message }}</p>

                    <a href="{{ filament()->getLoginUrl() }}" class="mt-2 inline-block text-sm font-semibold text-tanaki-teal hover:underline">Back to sign in</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
