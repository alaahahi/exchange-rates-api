<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D2149">
    <title>@yield('title', 'لوحة التحكم') — dinar-now</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0D2149;
            --navy-dark: #081530;
            --gold: #C29953;
            --gold-soft: #e8d4a8;
            --surface: #f3f4f8;
            --card: #ffffff;
            --ink: #0D2149;
            --muted: #5c6578;
            --line: #e2e6ef;
            --up: #1a7a4c;
            --down: #b42318;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, sans-serif;
            background: var(--surface);
            color: var(--ink);
        }
        a { color: inherit; text-decoration: none; }
        .shell { min-height: 100vh; display: flex; flex-direction: column; }
        .topbar {
            background: linear-gradient(180deg, rgba(13,33,73,.96), rgba(8,21,48,.94));
            color: #fff;
            border-bottom: 1px solid rgba(194,153,83,.35);
        }
        .topbar-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .brand { display: flex; align-items: center; gap: 0.75rem; }
        .brand img {
            height: 42px;
            width: auto;
            border-radius: 8px;
            background: #fff;
            padding: 3px;
        }
        .brand strong { display: block; font-size: 0.95rem; }
        .brand span { display: block; font-size: 0.75rem; color: rgba(255,255,255,.65); }
        .nav {
            display: flex;
            gap: 0.35rem;
            margin-inline-start: auto;
            flex-wrap: wrap;
        }
        .nav a, .nav button {
            border: 0;
            background: rgba(255,255,255,.06);
            color: rgba(255,255,255,.8);
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            font: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }
        .nav a.is-active, .nav a:hover, .nav button:hover {
            background: var(--gold);
            color: var(--navy);
        }
        .content {
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 1.25rem 1rem 2rem;
            flex: 1;
        }
        .page-title {
            margin: 0 0 1rem;
            font-size: 1.35rem;
        }
        .grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        @media (min-width: 800px) {
            .grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(13,33,73,.04);
        }
        .card .label { margin: 0; color: var(--muted); font-size: 0.78rem; font-weight: 600; }
        .card .value { margin: 0.35rem 0 0; font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .card .value.up { color: var(--up); }
        .card .value.down { color: var(--down); }
        .flash {
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 0.85rem;
            background: #e8f7ef;
            color: #146c43;
            border: 1px solid #b6e4c8;
            font-weight: 600;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { padding: 0.65rem 0.5rem; border-bottom: 1px solid var(--line); text-align: start; vertical-align: middle; }
        th { font-size: 0.78rem; color: var(--muted); }
        input[type="text"], input[type="number"], input[type="email"], input[type="password"] {
            width: 100%;
            min-width: 5rem;
            border: 1px solid var(--line);
            border-radius: 0.65rem;
            padding: 0.45rem 0.55rem;
            font: inherit;
            background: #fff;
            color: var(--ink);
        }
        input:focus { outline: 2px solid var(--gold); outline-offset: 1px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 0.7rem;
            padding: 0.5rem 0.9rem;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            background: var(--gold);
            color: var(--navy);
        }
        .btn:hover { filter: brightness(1.05); }
        .btn-ghost {
            background: transparent;
            border: 1px solid var(--line);
            color: var(--muted);
        }
        .muted { color: var(--muted); }
        .code {
            display: inline-flex;
            min-width: 2.6rem;
            justify-content: center;
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
            background: rgba(13,33,73,.08);
            font-weight: 700;
            font-size: 0.78rem;
        }
        .row-actions { display: flex; gap: 0.4rem; align-items: center; }
        .check { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: var(--muted); }
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ route('admin.dashboard') }}" class="brand">
                <img src="{{ asset('logo.png') }}" alt="dinar-now">
                <div>
                    <strong>dinar-now</strong>
                    <span>لوحة التحكم</span>
                </div>
            </a>
            <nav class="nav" aria-label="Admin">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">الرئيسية</a>
                <a href="{{ route('admin.exchange-rates.index') }}" class="{{ request()->routeIs('admin.exchange-rates.*') ? 'is-active' : '' }}">أسعار الصرف</a>
                <a href="{{ route('admin.gold-rates.index') }}" class="{{ request()->routeIs('admin.gold-rates.*') ? 'is-active' : '' }}">الذهب</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">المستخدمون</a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit">خروج</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="content">
        @if (session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
