@extends('admin.layouts.app')

@section('title', 'الإعدادات')

@section('content')
    <h1 class="page-title">الإعدادات</h1>
    <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem;">هوية الموقع، السجلات، والمايغريشن.</p>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
        <div class="card" style="text-align:center;">
            <p class="label">لوغو الموقع</p>
            @if ($logoExists)
                <img
                    src="{{ $logoUrl }}"
                    alt="dinar-now logo"
                    style="display:block;margin:1rem auto 0.75rem;max-height:96px;width:auto;border-radius:12px;background:#fff;padding:8px;box-shadow:0 0 0 1px var(--line);"
                >
                <p class="muted" style="margin:0;font-size:0.8rem;">{{ $logoUrl }}</p>
            @else
                <p class="muted" style="margin:1rem 0 0;">اللوغو غير موجود في public/logo.png</p>
            @endif
        </div>

        <div class="card">
            <p class="label">معلومات النظام</p>
            <p style="margin:0.75rem 0 0;font-weight:700;">{{ $appName }}</p>
            <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem;word-break:break-all;">{{ $appUrl }}</p>
            <p class="muted" style="margin:0.75rem 0 0;font-size:0.85rem;">
                المصدر: <strong>{{ $sourceLabel }}</strong> ({{ $provider }})
            </p>
            <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem;">
                البث الحي: <strong>{{ $liveEnabled ? 'مفعّل' : 'متوقف' }}</strong>
                · الكاش: <strong>{{ $cacheTtl }} ثانية</strong>
            </p>
        </div>
    </div>

    <div class="card" style="margin-top:0.85rem;" id="analytics">
        <p class="label">Google Analytics</p>
        <p class="muted" style="margin:0.5rem 0 0;font-size:0.9rem;line-height:1.7;">
            Measurement ID:
            <strong style="direction:ltr;display:inline-block;">{{ $gaMeasurementId ?: '—' }}</strong>
        </p>
        <p class="muted" style="margin:0.55rem 0 0;font-size:0.85rem;line-height:1.7;">
            نعم يمكن عرض إحصائيات غوغل داخل الأدمن، لكن يحتاج ربط
            <strong>GA4 Data API</strong>
            (حساب خدمة + صلاحيات على الـ Property). حالياً يمكنك فتح لوحة غوغل مباشرة:
        </p>
        <div class="row-actions" style="margin-top:0.85rem;flex-wrap:wrap;">
            <a class="btn" href="{{ $gaDashboardUrl }}" target="_blank" rel="noopener noreferrer">فتح Google Analytics</a>
        </div>
    </div>

    <div class="card" style="margin-top:0.85rem;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
            <div>
                <p class="label" style="margin:0;">تشغيل المايغريشن</p>
                <p class="muted" style="margin:0.4rem 0 0;font-size:0.85rem;">
                    المعلّقة حالياً:
                    <strong style="color:{{ $pendingCount > 0 ? 'var(--down)' : 'var(--up)' }};">{{ $pendingCount }}</strong>
                </p>
            </div>

            <form method="POST" action="{{ route('admin.settings.migrate') }}" onsubmit="return confirm('تشغيل php artisan migrate --force الآن؟');">
                @csrf
                <label class="migrate-toggle">
                    <input type="checkbox" name="confirm_migrate" value="1" required>
                    <span class="migrate-toggle__track" aria-hidden="true"></span>
                    <span class="migrate-toggle__label">تفعيل ثم تشغيل</span>
                </label>
                <button class="btn" type="submit" style="margin-inline-start:0.5rem;">تشغيل المايغريشن</button>
            </form>
        </div>

        @if ($pendingCount > 0)
            <ul style="margin:1rem 0 0;padding-inline-start:1.1rem;color:var(--muted);font-size:0.85rem;">
                @foreach ($pendingMigrations as $migration)
                    <li>{{ $migration }}</li>
                @endforeach
            </ul>
        @endif

        @if (session('migrate_output'))
            <pre class="log-box">{{ session('migrate_output') }}</pre>
        @endif
    </div>

    <div class="card" style="margin-top:0.85rem;" id="logs">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
            <div>
                <p class="label" style="margin:0;">سجل الأخطاء</p>
                <p class="muted" style="margin:0.4rem 0 0;font-size:0.85rem;">
                    @if ($log['exists'])
                        الحجم: <strong>{{ $log['size_label'] }}</strong>
                        · آخر تعديل: <strong>{{ $log['modified_at'] }}</strong>
                        · أسطر معروضة تقريباً: <strong>{{ $log['lines'] }}</strong>
                    @else
                        لا يوجد ملف سجل بعد.
                    @endif
                </p>
            </div>
            @if (Route::has('admin.settings.logs.clear'))
                <form method="POST" action="{{ route('admin.settings.logs.clear') }}" onsubmit="return confirm('تفريغ laravel.log بالكامل؟');">
                    @csrf
                    <button class="btn btn-ghost" type="submit" style="color:var(--down);border-color:#f5c2c0;" @if (! $log['exists'] || $log['size'] === 0) disabled @endif>
                        تفريغ السجل
                    </button>
                </form>
            @endif
        </div>

        <pre class="log-box" style="max-height:420px;overflow:auto;">{{ $log['content'] !== '' ? $log['content'] : 'السجل فارغ.' }}</pre>
        <p class="muted" style="margin:0.55rem 0 0;font-size:0.75rem;direction:ltr;text-align:start;">{{ $log['path'] }}</p>
    </div>

    <style>
        .log-box {
            margin: 1rem 0 0;
            padding: 0.85rem 1rem;
            border-radius: 0.85rem;
            background: #0b1220;
            color: #d7e0ef;
            border: 1px solid #334560;
            font-size: 0.75rem;
            white-space: pre-wrap;
            direction: ltr;
            text-align: start;
            line-height: 1.45;
        }
        .migrate-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            user-select: none;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--muted);
            vertical-align: middle;
        }
        .migrate-toggle input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .migrate-toggle__track {
            width: 2.6rem;
            height: 1.45rem;
            border-radius: 999px;
            background: #c5ccd8;
            position: relative;
            transition: background .2s ease;
            flex-shrink: 0;
        }
        .migrate-toggle__track::after {
            content: '';
            position: absolute;
            top: 0.18rem;
            inset-inline-start: 0.18rem;
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
            transition: transform .2s ease;
        }
        .migrate-toggle input:checked + .migrate-toggle__track {
            background: var(--gold);
        }
        .migrate-toggle input:checked + .migrate-toggle__track::after {
            transform: translateX(-1.15rem);
        }
        html[dir="rtl"] .migrate-toggle input:checked + .migrate-toggle__track::after {
            transform: translateX(1.15rem);
        }
        .migrate-toggle input:focus-visible + .migrate-toggle__track {
            outline: 2px solid var(--gold);
            outline-offset: 2px;
        }
    </style>
@endsection
