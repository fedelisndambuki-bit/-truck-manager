<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Accept Invitation — Tanaki Hub</title>
    @livewireStyles
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f3f4f6; margin: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,.08); padding: 40px; max-width: 420px; width: 100%; }
        .card h1 { font-size: 22px; margin: 0 0 8px; color: #111827; }
        .card p.subtitle { color: #6b7280; font-size: 14px; margin: 0 0 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
        button { width: 100%; padding: 12px; border: none; border-radius: 8px; background: #2f4f4f; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; }
        button:hover { background: #24403f; }
        .error { color: #dc2626; font-size: 12px; margin-top: -12px; margin-bottom: 12px; }
        .invalid { text-align: center; color: #6b7280; }
        .success { text-align: center; color: #059669; }
    </style>
</head>
<body>
    <div class="card">
        @if ($invalid)
            <h1>Invitation not valid</h1>
            <p class="invalid">This invitation link has expired or has already been used. Please contact the person who invited you for a new link.</p>
        @elseif ($accepted)
            <h1>Welcome aboard!</h1>
            <p class="success">Your account is ready. Redirecting…</p>
        @else
            <h1>Accept Invitation</h1>
            <p class="subtitle">Set your name and password to join Tanaki Hub{{ $invitation?->role ? ' as a ' . $invitation->role : '' }}.</p>

            <form wire:submit="accept">
                <label>Email</label>
                <input type="text" value="{{ $invitation?->email }}" disabled style="background:#f9fafb;color:#6b7280;" />

                <label>Full Name</label>
                <input type="text" wire:model="name" />
                @error('name') <div class="error">{{ $message }}</div> @enderror

                <label>Password</label>
                <input type="password" wire:model="password" />
                @error('password') <div class="error">{{ $message }}</div> @enderror

                <label>Confirm Password</label>
                <input type="password" wire:model="password_confirmation" />

                <button type="submit">Set Password &amp; Continue</button>
            </form>
        @endif
    </div>
    @livewireScripts
</body>
</html>
