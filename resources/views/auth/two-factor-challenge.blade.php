<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Verification</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; }
        .wrap { max-width:420px; margin:8vh auto; background:#fff; border:1px solid #d1d5db; border-radius:10px; padding:24px; }
        h1 { margin:0 0 8px; font-size:20px; color:#1f2937; }
        p { margin:0 0 14px; color:#4b5563; font-size:14px; }
        input { width:100%; padding:10px; border:1px solid #9ca3af; border-radius:6px; font-size:16px; box-sizing:border-box; }
        button { margin-top:12px; padding:10px 12px; border:0; border-radius:6px; cursor:pointer; }
        .primary { background:#2f4f4f; color:#fff; }
        .secondary { background:#dc2626; color:#fff; }
        .error { color:#b91c1c; font-size:13px; margin-top:8px; }
        .status { color:#065f46; font-size:13px; margin-top:8px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Two-Factor Verification</h1>
    @if(($method ?? 'email') === 'totp')
        <p>Enter the 6-digit code from your authenticator app.</p>
    @else
        <p>Enter the 6-digit code sent to <strong>{{ $email }}</strong>.</p>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        <input type="text" name="code" maxlength="10" placeholder="Enter verification code" required autofocus>
        <button type="submit" class="primary">Verify & Continue</button>
    </form>

    @if(($method ?? 'email') !== 'totp')
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="secondary">Resend Code</button>
        </form>
    @endif

    @if ($errors->has('code'))
        <div class="error">{{ $errors->first('code') }}</div>
    @endif
    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif
</div>
</body>
</html>
