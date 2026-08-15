    <div class="tk-split min-h-screen w-full">
        {{-- ══════════════════════════ LEFT — HERO / MARKETING PANEL ══════════════════════════ --}}
        {{-- Identical hero to login/register — the whole auth system must read as one product. --}}
        <div class="tk-hero relative flex flex-col overflow-y-auto pb-10 pl-6 pr-6 pt-10 text-white lg:pb-12 lg:pl-20 lg:pr-16 lg:pt-[70px]">
            <x-auth.logo layout="horizontal" :mark-size="44" />

            <div class="mt-8 max-w-[540px]">
                <h1 class="text-[52px] font-extrabold leading-[1.12] tracking-tight">
                    One Platform.<br>
                    <span class="text-tanaki-teal">Every Operation.</span>
                </h1>

                <p class="mt-4 max-w-[480px] text-base leading-relaxed text-slate-300">
                    Manage your fleet, construction projects, quarry operations, workshops, fuel, weighbridge, procurement, HR, finance and assets from one intelligent industrial operating platform.
                </p>

                <div class="tk-module-grid mt-7 max-w-[560px]">
                    <x-auth.feature-card title="Fleet Management" path='<path d="M3 17h13V7H3v10zm13-7h3l3 4v3h-6v-7zM6 20a2 2 0 100-4 2 2 0 000 4zm12 0a2 2 0 100-4 2 2 0 000 4z"/>' />
                    <x-auth.feature-card title="Construction Management" path='<path d="M6 21V9l6-4 6 4v12M9 21v-6h6v6"/>' />
                    <x-auth.feature-card title="Quarry Operations" path='<path d="M3 20l6-11 4 7 3-5 5 9H3z"/>' />
                    <x-auth.feature-card title="Workshop Management" path='<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.1-3.1a5 5 0 01-6.6 6.6L5.4 21.4a2 2 0 01-2.8-2.8L11.8 9.9a5 5 0 016.6-6.6l-3.1 3.1z"/>' />
                    <x-auth.feature-card title="Weighbridge" path='<path d="M12 3v18M5 7l-3 6a3 3 0 006 0l-3-6zm14 0l-3 6a3 3 0 006 0l-3-6zM5 7h14"/>' />
                    <x-auth.feature-card title="Fuel Management" path='<path d="M3 22V6a2 2 0 012-2h6a2 2 0 012 2v16M3 22h10m5-13h1.5a1.5 1.5 0 011.5 1.5V18a1.5 1.5 0 01-3 0V13l-2-2M5 10h6"/>' />
                    <x-auth.feature-card title="Finance & Accounting" path='<circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.4 0 1.3-1.3 2.3-3 2.3s-3-1.1-3-2.5"/>' />
                    <x-auth.feature-card title="HR & Payroll" path='<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c.6-3.4 3.2-5.6 6.5-5.6s5.9 2.2 6.5 5.6"/><circle cx="17.5" cy="8.5" r="2.6"/><path d="M16 14.7c2.6.4 4.4 2.2 4.9 5.3"/>' />
                </div>
            </div>

            <div class="hidden min-h-6 flex-1 lg:block"></div>

            <div class="hidden max-w-[560px] lg:block">
                <x-auth.dashboard-preview />
                <x-auth.foot-strip />
            </div>
        </div>

        {{-- ══════════════════════════ RIGHT — REQUEST ACCESS PANEL ══════════════════════════ --}}
        <div class="flex flex-col bg-tanaki-bg-right lg:h-screen">
            <div class="flex flex-1 items-center justify-center overflow-y-auto px-6 py-8 lg:px-8 lg:py-8">
                <div class="tk-auth-card my-auto flex w-full max-w-[560px] flex-col gap-5 rounded-[20px] bg-white px-10 py-8">
                    <x-auth.logo layout="stacked" :mark-size="58" />

                    @if ($submitted)
                        <div class="text-center py-6">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-tanaki-teal/10">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7 text-tanaki-teal">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h2 class="text-[22px] font-bold leading-tight text-tanaki-ink">Check your email</h2>
                            <p class="mt-2 text-sm text-tanaki-grey">
                                Thank you for your interest in Tanaki Hub. We've sent a verification link to
                                <span class="font-semibold text-tanaki-ink">{{ $requester_email }}</span>.
                                Once verified, your organization request will be reviewed — Tanaki Hub will contact you if additional information is required.
                            </p>
                            <a href="{{ filament()->getLoginUrl() }}" class="mt-6 inline-block text-sm font-semibold text-tanaki-teal hover:underline">Back to sign in</a>
                        </div>
                    @else
                        <div class="text-center">
                            <h2 class="text-[27px] font-bold leading-tight text-tanaki-ink">Create your Tanaki Hub account</h2>
                            <p class="mt-1 text-sm text-tanaki-grey">Set up your organization and tell us how you operate.</p>
                        </div>

                        @if ($errorMessage)
                            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                                {{ $errorMessage }}
                            </div>
                        @endif

                        <form wire:submit="submit" class="tk-auth-form flex flex-col gap-5">
                            <div>
                                <div class="mb-3 text-xs font-bold uppercase tracking-widest text-tanaki-grey-light">Contact Information</div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <label class="mb-1 block text-sm font-medium text-tanaki-ink">Full Name <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="requester_name" class="fi-input w-full rounded-lg border border-tanaki-border px-3 py-2 text-sm" required>
                                        @error('requester_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-tanaki-ink">Work Email <span class="text-red-500">*</span></label>
                                        <input type="email" wire:model="requester_email" class="fi-input w-full rounded-lg border border-tanaki-border px-3 py-2 text-sm" required>
                                        @error('requester_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-tanaki-ink">Phone Number <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="requester_phone" class="fi-input w-full rounded-lg border border-tanaki-border px-3 py-2 text-sm" required>
                                        @error('requester_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-3 text-xs font-bold uppercase tracking-widest text-tanaki-grey-light">Organization Information</div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <label class="mb-1 block text-sm font-medium text-tanaki-ink">Company / Organization Name <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="organization_name" class="fi-input w-full rounded-lg border border-tanaki-border px-3 py-2 text-sm" required>
                                        @error('organization_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-tanaki-ink">Country <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <select wire:model="organization_country" class="tk-plain-select w-full appearance-none rounded-lg border border-tanaki-border bg-white px-3 py-2 pr-9 text-sm" required>
                                                <option value="">Select...</option>
                                                @foreach (self::COUNTRIES as $country)
                                                    <option value="{{ $country }}">{{ $country }}</option>
                                                @endforeach
                                            </select>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-tanaki-grey-light"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                        </div>
                                        @error('organization_country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-tanaki-ink">Company Type <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <select wire:model="organization_type" class="tk-plain-select w-full appearance-none rounded-lg border border-tanaki-border bg-white px-3 py-2 pr-9 text-sm" required>
                                                <option value="">Select...</option>
                                                @foreach (self::COMPANY_TYPES as $type)
                                                    <option value="{{ $type }}">{{ $type }}</option>
                                                @endforeach
                                            </select>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-tanaki-grey-light"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                        </div>
                                        @error('organization_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="col-span-2">
                                        <label class="mb-1 block text-sm font-medium text-tanaki-ink">Operation Size</label>
                                        <div class="relative">
                                            <select wire:model="organization_size" class="tk-plain-select w-full appearance-none rounded-lg border border-tanaki-border bg-white px-3 py-2 pr-9 text-sm">
                                                <option value="">Select...</option>
                                                @foreach (self::OPERATION_SIZES as $size)
                                                    <option value="{{ $size }}">{{ $size }} vehicles/equipment</option>
                                                @endforeach
                                            </select>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-tanaki-grey-light"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-3 text-xs font-bold uppercase tracking-widest text-tanaki-grey-light">Operations of Interest</div>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                                    @foreach (self::OPERATIONS as $operation)
                                        <label class="flex items-center gap-2 text-sm text-tanaki-ink">
                                            <input type="checkbox" wire:model="operations_of_interest" value="{{ $operation }}" class="rounded border-tanaki-border text-tanaki-teal">
                                            {{ $operation }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-tanaki-ink">Tell us about your operation <span class="text-tanaki-grey-light">(optional)</span></label>
                                <textarea wire:model="operation_description" rows="3" class="fi-input w-full rounded-lg border border-tanaki-border px-3 py-2 text-sm"></textarea>
                            </div>

                            <div>
                                <label class="flex items-start gap-2 text-sm text-tanaki-ink">
                                    <input type="checkbox" wire:model="terms" class="mt-0.5 rounded border-tanaki-border text-tanaki-teal" required>
                                    <span>I agree to the <a href="{{ url('/terms') }}" target="_blank" rel="noopener" class="underline hover:no-underline">Terms of Service</a> and <a href="{{ url('/privacy') }}" target="_blank" rel="noopener" class="underline hover:no-underline">Privacy Policy</a>.</span>
                                </label>
                                @error('terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <p class="mt-2 text-xs text-tanaki-grey-light">Your information is used only to review this request and will be retained per our data retention policy.</p>
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-tanaki-navy px-6 py-3 text-sm font-bold text-white transition hover:opacity-90" style="background-color:#2F4F4F;">
                                Request Access
                            </button>
                        </form>

                        <div class="text-center text-sm text-tanaki-grey">
                            Already have an account?
                            <a href="{{ filament()->getLoginUrl() }}" class="font-semibold text-tanaki-teal hover:underline">Sign in</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-center gap-4 px-8 pb-4 text-center text-[11.5px] text-tanaki-grey-light">
                <span>&copy; {{ now()->year }} Tanaki Technologies Ltd.</span>
                <span>Version 1.0.0</span>
                <a href="{{ url('/privacy') }}" class="hover:text-tanaki-grey">Privacy</a>
                <a href="{{ url('/terms') }}" class="hover:text-tanaki-grey">Terms</a>
                <a href="{{ url('/support') }}" class="hover:text-tanaki-grey">Support</a>
            </div>
        </div>
    </div>
