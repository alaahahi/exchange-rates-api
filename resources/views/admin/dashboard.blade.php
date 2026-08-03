@extends('admin.layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <h1 class="page-title">لوحة التحكم</h1>

    <div class="card" style="margin-bottom:0.85rem;">
        <p class="label">مصدر الأسعار</p>
        <p class="value" style="font-size:1.1rem;">{{ $sourceMeta['source_label'] ?? '—' }}</p>
        <p class="muted" style="margin:0.45rem 0 0;font-size:0.85rem;">
            المفتاح: <strong>{{ $sourceMeta['source'] ?? '—' }}</strong>
            · البث الحي: <strong>{{ !empty($sourceMeta['live_enabled']) ? 'مفعّل' : 'متوقف' }}</strong>
            · كاش السيرفر: <strong>{{ $sourceMeta['cache_ttl'] ?? 120 }} ثانية</strong>
        </p>
        @if (!empty($sourceMeta['last_synced_at']))
            <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem;">
                آخر مزامنة من المصدر: <strong>{{ $sourceMeta['last_synced_at'] }}</strong>
            </p>
        @endif
        @if ($usd)
            <div class="row-actions" style="margin-top:0.85rem;flex-wrap:wrap;gap:0.75rem;">
                <div>
                    <span class="muted" style="font-size:0.75rem;">شراء المصدر (USD)</span>
                    <div class="value up" style="font-size:1.25rem;">{{ rtrim(rtrim(number_format((float) $usd->buy_rate, 4, '.', ''), '0'), '.') }}</div>
                </div>
                <div>
                    <span class="muted" style="font-size:0.75rem;">مبيع المصدر (USD)</span>
                    <div class="value down" style="font-size:1.25rem;">{{ rtrim(rtrim(number_format((float) $usd->sell_rate, 4, '.', ''), '0'), '.') }}</div>
                </div>
                <div>
                    <span class="muted" style="font-size:0.75rem;">source على الصف</span>
                    <div class="value" style="font-size:0.95rem;">{{ $usd->source ?: '—' }}</div>
                </div>
            </div>
        @endif
    </div>

    <div class="grid">
        <div class="card">
            <p class="label">مبيع الدولار</p>
            <p class="value down">{{ $usd ? rtrim(rtrim(number_format((float) $usd->sell_rate, 4, '.', ''), '0'), '.') : '—' }}</p>
        </div>
        <div class="card">
            <p class="label">شراء الدولار</p>
            <p class="value up">{{ $usd ? rtrim(rtrim(number_format((float) $usd->buy_rate, 4, '.', ''), '0'), '.') : '—' }}</p>
        </div>
        <div class="card">
            <p class="label">العملات النشطة</p>
            <p class="value">{{ $activeExchangeCount }} / {{ $exchangeCount }}</p>
        </div>
        <div class="card">
            <p class="label">الذهب النشط</p>
            <p class="value">{{ $activeGoldCount }} / {{ $goldCount }}</p>
        </div>
    </div>

    <div class="grid" style="margin-top:0.85rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="card">
            <p class="label">حالة السوق</p>
            <p class="value" style="font-size:1.05rem;">{{ $market['status_label'] ?? '—' }}</p>
            <p class="muted" style="margin:0.4rem 0 0;font-size:0.8rem;">{{ $market['local_time'] ?? '' }} · {{ $market['timezone'] ?? '' }}</p>
        </div>
        <div class="card">
            <p class="label">آخر تحديث للصرف</p>
            <p class="value" style="font-size:1rem;">{{ $latestExchangeUpdate ?: '—' }}</p>
        </div>
        <div class="card">
            <p class="label">آخر تحديث للذهب</p>
            <p class="value" style="font-size:1rem;">{{ $latestGoldUpdate ?: '—' }}</p>
        </div>
    </div>

    <div class="card" style="margin-top:0.85rem;">
        <p class="label">اختصارات</p>
        <div class="row-actions" style="margin-top:0.75rem;flex-wrap:wrap;">
            <a class="btn" href="{{ route('admin.exchange-rates.index') }}">تعديل أسعار الصرف</a>
            <a class="btn btn-ghost" href="{{ route('admin.gold-rates.index') }}">تعديل أسعار الذهب</a>
                <a class="btn btn-ghost" href="{{ route('admin.users.index') }}">المستخدمون</a>
                <a class="btn btn-ghost" href="{{ route('admin.settings.index') }}">الإعدادات</a>
        </div>
    </div>
@endsection
