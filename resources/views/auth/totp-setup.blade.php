<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Authenticator</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; }
        .wrap { max-width:520px; margin:6vh auto; background:#fff; border:1px solid #d1d5db; border-radius:10px; padding:24px; }
        h1 { margin:0 0 8px; font-size:20px; color:#1f2937; }
        p { margin:0 0 10px; color:#4b5563; font-size:14px; }
        code { background:#f3f4f6; padding:2px 6px; border-radius:4px; }
        input { width:100%; padding:10px; border:1px solid #9ca3af; border-radius:6px; font-size:16px; box-sizing:border-box; }
        button { margin-top:12px; padding:10px 12px; border:0; border-radius:6px; cursor:pointer; }
        .primary { background:#2f4f4f; color:#fff; }
        .danger { background:#dc2626; color:#fff; }
        .row { display:flex; gap:8px; flex-wrap:wrap; }
        .error { color:#b91c1c; font-size:13px; margin-top:8px; }
        .status { color:#065f46; font-size:13px; margin-top:8px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Authenticator Setup (TOTP)</h1>
    <p>1. Open Google Authenticator (or any TOTP app).</p>
    <p>2. Scan QR code or enter secret manually: <code>{{ $secret }}</code></p>

    <div style="margin:12px 0; text-align:center;">
        <img src="{{ $qrUrl }}" alt="TOTP QR" style="max-width:220px; border:1px solid #e5e7eb; border-radius:8px;">
    </div>

    <form method="POST" action="{{ route('two-factor.totp.enable') }}">
        @csrf
        <input type="text" name="code" maxlength="6" placeholder="Enter app code to confirm" required>
        <button type="submit" class="primary">Enable Authenticator</button>
    </form>

    <form method="POST" action="{{ route('two-factor.totp.disable') }}">
        @csrf
        <button type="submit" class="danger">Disable Authenticator</button>
    </form>

    @if ($errors->has('code'))
        <div class="error">{{ $errors->first('code') }}</div>
    @endif
    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif
</div>
</body>
</html>
