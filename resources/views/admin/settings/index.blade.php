@extends('admin.layouts.app')

@section('title', 'الإعدادات')

@section('content')
    <h1 class="page-title">الإعدادات</h1>
    <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem;">هوية الموقع، إحصائيات غوغل، السجلات، والمايغريشن.</p>

    <div class="card" id="analytics" style="margin-bottom:0.85rem;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;">
            <div>
                <p class="label" style="margin:0;">Google Analytics — الشارت</p>
                <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem;">
                    Measurement ID:
                    <strong style="direction:ltr;display:inline-block;">{{ $ga['measurement_id'] ?: '—' }}</strong>
                </p>
            </div>
        </div>

        @if ($ga['mode'] === 'embed' && $ga['embed_url'])
            <div style="margin-top:1rem;border-radius:1rem;overflow:hidden;border:1px solid var(--line);background:#fff;">
                <iframe
                    src="{{ $ga['embed_url'] }}"
                    title="Google Analytics charts"
                    style="width:100%;min-height:520px;border:0;display:block;"
                    loading="lazy"
                    allowfullscreen
                ></iframe>
            </div>
        @elseif ($ga['mode'] === 'api')
            <div class="grid" style="margin-top:1rem;grid-template-columns:repeat(3,minmax(0,1fr));">
                <div class="card" style="box-shadow:none;">
                    <p class="label">المستخدمون (7 أيام)</p>
                    <p class="value">{{ number_format($ga['totals']['active_users'], 0, '.', '') }}</p>
                </div>
                <div class="card" style="box-shadow:none;">
                    <p class="label">الجلسات</p>
                    <p class="value">{{ number_format($ga['totals']['sessions'], 0, '.', '') }}</p>
                </div>
                <div class="card" style="box-shadow:none;">
                    <p class="label">مشاهدات الصفحات</p>
                    <p class="value">{{ number_format($ga['totals']['page_views'], 0, '.', '') }}</p>
                </div>
            </div>
            <div style="margin-top:1rem;background:#fff;border:1px solid var(--line);border-radius:1rem;padding:0.75rem;">
                <canvas id="ga-traffic-chart" height="110"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script>
                (function () {
                    var labels = @json($ga['labels']);
                    var users = @json($ga['active_users']);
                    var sessions = @json($ga['sessions']);
                    var views = @json($ga['page_views']);
                    var ctx = document.getElementById('ga-traffic-chart');
                    if (!ctx || !window.Chart) return;
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'المستخدمون',
                                    data: users,
                                    borderColor: '#0D2149',
                                    backgroundColor: 'rgba(13,33,73,0.12)',
                                    tension: 0.3,
                                    fill: true,
                                },
                                {
                                    label: 'الجلسات',
                                    data: sessions,
                                    borderColor: '#C29953',
                                    backgroundColor: 'rgba(194,153,83,0.12)',
                                    tension: 0.3,
                                    fill: true,
                                },
                                {
                                    label: 'المشاهدات',
                                    data: views,
                                    borderColor: '#1a7a4c',
                                    backgroundColor: 'rgba(26,122,76,0.10)',
                                    tension: 0.3,
                                    fill: false,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom' }
                            },
                            scales: {
                                y: { beginAtZero: true, ticks: { precision: 0 } }
                            }
                        }
                    });
                })();
            </script>
        @else
            <div style="margin-top:1rem;padding:1rem;border-radius:1rem;border:1px dashed var(--line);background:var(--surface,#f3f4f8);">
                <p style="margin:0;font-weight:700;">لتظهر الشارت هنا (بدون تحويل لصفحة غوغل):</p>
                <ol style="margin:0.75rem 0 0;padding-inline-start:1.2rem;color:var(--muted);font-size:0.9rem;line-height:1.8;">
                    <li>
                        <strong>الأسهل:</strong> من Looker Studio اعمل تقرير مربوط بـ GA4 → File → Embed report → انسخ الرابط وضعه في
                        <code>GA_EMBED_URL</code>
                    </li>
                    <li>
                        <strong>أو API:</strong> أنشئ Service Account في Google Cloud، فعّل Analytics Data API، أضف الإيميل Viewer على الـ Property، ثم ضع:
                        <code>GA_PROPERTY_ID</code>
                        وملف JSON في
                        <code>storage/app/google/service-account.json</code>
                    </li>
                </ol>
                @if ($ga['error'])
                    <p class="muted" style="margin:0.85rem 0 0;font-size:0.85rem;color:var(--down);">{{ $ga['error'] }}</p>
                @endif
            </div>
        @endif
    </div>

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
        @media (max-width: 800px) {
            #analytics .grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection
