<x-filament-panels::page>
    <div style="background-color:#2f4f4f;padding:20px 24px;color:#fff;border-radius:10px;margin-bottom:20px;">
        <div style="font-size:18px;font-weight:600;">Profile</div>
        <div style="font-size:13px;opacity:.85;margin-top:2px;">
            Set the name and photo shown for your account. If you don't add a photo, your initials are shown instead.
        </div>
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="fi-form-actions" style="display:flex;align-items:center;gap:10px;margin-top:20px;">
            <x-filament::button type="submit">
                Save changes
            </x-filament::button>

            @if (auth()->user()?->avatar_path)
                <x-filament::button color="gray" wire:click="removePhoto">
                    Remove photo
                </x-filament::button>
            @endif
        </div>
    </form>
</x-filament-panels::page>
