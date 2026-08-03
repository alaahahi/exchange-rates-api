@extends('admin.layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <h1 class="page-title">لوحة التحكم</h1>

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
        <div class="row-actions" style="margin-top:0.75rem;">
            <a class="btn" href="{{ route('admin.exchange-rates.index') }}">تعديل أسعار الصرف</a>
            <a class="btn btn-ghost" href="{{ route('admin.gold-rates.index') }}">تعديل أسعار الذهب</a>
        </div>
    </div>
@endsection
