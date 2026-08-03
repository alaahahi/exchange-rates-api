<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D2149">
    <title>تسجيل الدخول — dinar-now</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0D2149; --gold:#C29953; --surface:#eef0f5; --card:#fff; --line:#e2e6ef; --muted:#5c6578; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, sans-serif;
            background:
                radial-gradient(ellipse 70% 50% at 100% 0%, rgba(194,153,83,.18), transparent 55%),
                linear-gradient(180deg, #0D2149 0%, #081530 42%, var(--surface) 42%);
            color: var(--navy);
            padding: 1rem;
        }
        .card {
            width: min(100%, 420px);
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 18px 50px rgba(8,21,48,.18);
        }
        .logo {
            display: block; margin: 0 auto 1rem; height: 64px; width: auto;
            background: #fff; border-radius: 12px; padding: 4px;
            box-shadow: 0 0 0 1px rgba(13,33,73,.08);
        }
        h1 { margin: 0; text-align: center; font-size: 1.25rem; }
        p.sub { margin: 0.4rem 0 1.25rem; text-align: center; color: var(--muted); font-size: 0.9rem; }
        label { display: block; margin-bottom: 0.35rem; font-size: 0.85rem; font-weight: 600; }
        input {
            width: 100%; border: 1px solid var(--line); border-radius: 0.75rem;
            padding: 0.7rem 0.8rem; font: inherit; margin-bottom: 0.9rem;
        }
        input:focus { outline: 2px solid var(--gold); outline-offset: 1px; }
        .error { color: #b42318; font-size: 0.85rem; margin: -0.45rem 0 0.8rem; }
        .btn {
            width: 100%; border: 0; border-radius: 0.8rem; padding: 0.75rem 1rem;
            background: var(--gold); color: var(--navy); font: inherit; font-weight: 700; cursor: pointer;
        }
        .hint { margin-top: 1rem; text-align: center; color: var(--muted); font-size: 0.8rem; }
        .remember { display: flex; align-items: center; gap: 0.4rem; margin: 0 0 1rem; color: var(--muted); font-size: 0.85rem; }
        .remember input { width: auto; margin: 0; }
    </style>
</head>
<body>
<form class="card" method="POST" action="{{ route('admin.login.store') }}">
    @csrf
    <img class="logo" src="{{ asset('logo.png') }}" alt="dinar-now">
    <h1>تسجيل الدخول</h1>
    <p class="sub">لوحة إدارة أسعار الصرف والذهب</p>

    <label for="email">البريد الإلكتروني</label>
    <input id="email" type="email" name="email" value="{{ old('email', 'admin@dinar.local') }}" required autocomplete="username">
    @error('email') <div class="error">{{ $message }}</div> @enderror

    <label for="password">كلمة المرور</label>
    <input id="password" type="password" name="password" required autocomplete="current-password">
    @error('password') <div class="error">{{ $message }}</div> @enderror

    <label class="remember">
        <input type="checkbox" name="remember" value="1">
        تذكرني
    </label>

    <button class="btn" type="submit">دخول</button>
    <p class="hint">تجريبي: admin@dinar.local / password</p>
</form>
</body>
</html>
