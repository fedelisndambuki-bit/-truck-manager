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
                <div class="tk-auth-card my-auto flex w-full max-w-[560px] flex-col gap-5 rounded-[20px] bg-white px-10 py-9">
                    <x-auth.logo layout="stacked" :mark-size="58" />

                    @if ($invalid)
                        <div class="text-center py-4">
                            <h2 class="text-[22px] font-bold leading-tight text-tanaki-ink">Link not valid</h2>
                            <p class="mt-2 text-sm text-tanaki-grey">This status link isn't valid. Please check your email for the correct link, or contact support.</p>
                        </div>
                    @else
                        @php
                            $status = $accessRequest->status;
                            $labels = \App\Models\AccessRequest::STATUS_LABELS;
                        @endphp

                        <div class="text-center">
                            <h2 class="text-[22px] font-bold leading-tight text-tanaki-ink">{{ $accessRequest->organization_name }}</h2>
                            <span class="mt-2 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">
                                {{ $labels[$status] ?? $status }}
                            </span>
                        </div>

                        @if ($status === \App\Models\AccessRequest::STATUS_ADDITIONAL_INFORMATION_REQUIRED)
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                                Tanaki Hub needs more information before continuing your review.
                            </div>

                            <div class="flex flex-col gap-3">
                                @foreach ($accessRequest->informationThreads as $entry)
                                    <div class="rounded-lg border border-tanaki-border px-4 py-3 text-sm {{ $entry->author_type === 'superadmin' ? 'bg-slate-50' : 'bg-tanaki-teal/5' }}">
                                        <div class="mb-1 text-xs font-bold uppercase tracking-wide text-tanaki-grey-light">
                                            {{ $entry->author_type === 'superadmin' ? 'Tanaki Hub' : 'You' }} · {{ $entry->created_at->format('d M Y, H:i') }}
                                        </div>
                                        <div class="text-tanaki-ink">{{ $entry->message }}</div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($justSubmitted)
                                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                                    Thanks — your response has been sent for review.
                                </div>
                            @else
                                <form wire:submit="submitReply" class="flex flex-col gap-3">
                                    <textarea wire:model="reply" rows="4" class="fi-input w-full rounded-lg border border-tanaki-border px-3 py-2 text-sm" placeholder="Type your response..."></textarea>
                                    @error('reply') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    <button type="submit" class="w-full rounded-xl px-6 py-3 text-sm font-bold text-white transition hover:opacity-90" style="background-color:#2F4F4F;">
                                        Send Response
                                    </button>
                                </form>
                            @endif
                        @elseif ($status === \App\Models\AccessRequest::STATUS_APPROVED)
                            <p class="text-center text-sm text-tanaki-grey">Your request has been approved! Check your email for the link to set your password and sign in.</p>
                        @elseif ($status === \App\Models\AccessRequest::STATUS_REJECTED)
                            <p class="text-center text-sm text-tanaki-grey">Your Tanaki Hub access request could not be approved at this time. If you believe this is in error, please contact support.</p>
                        @else
                            <p class="text-center text-sm text-tanaki-grey">Your organization request is being reviewed. Tanaki Hub will contact you if additional information is required.</p>
                        @endif
                    @endif

                    <a href="{{ filament()->getLoginUrl() }}" class="mt-2 text-center text-sm font-semibold text-tanaki-teal hover:underline">Back to sign in</a>
                </div>
            </div>
        </div>
    </div>
