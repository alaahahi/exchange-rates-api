@extends('admin.layouts.app')

@section('title', 'مكتبي')

@section('content')
    <h1 class="page-title">{{ $tenant->name }}</h1>
    <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem;">
        رابط العرض:
        <a href="{{ $frontendUrl }}/o/{{ $tenant->slug }}" target="_blank" rel="noopener" dir="ltr">{{ $frontendUrl }}/o/{{ $tenant->slug }}</a>
    </p>

    @if ($errors->any())
        <div class="flash" style="background:#fdecea;color:#b42318;border-color:#f5c2c0;">
            <ul style="margin:0;padding-inline-start:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom:1rem;">
        <p class="label" style="margin-bottom:0.75rem;">اللوغو ومصدر السعر</p>
        <form method="POST" action="{{ route('admin.office.update', request()->only('tenant_id')) }}" enctype="multipart/form-data" class="office-settings-grid">
            @csrf
            @method('PUT')
            <div>
                <label for="price_mode">مصدر السعر</label>
                <select id="price_mode" name="price_mode" style="width:100%;padding:0.45rem 0.55rem;border:1px solid var(--line);border-radius:0.65rem;font:inherit;">
                    <option value="panel" @selected(old('price_mode', $tenant->price_mode) === 'panel')>أسعار لوحة dinar-now</option>
                    <option value="custom" @selected(old('price_mode', $tenant->price_mode) === 'custom')>أسعاري (تعديل مباشر)</option>
                </select>
            </div>
            <div>
                <label for="logo">اللوغو</label>
                <input id="logo" type="file" name="logo" accept="image/*">
                @if ($tenant->logo_path)
                    <div style="margin-top:0.5rem;">
                        <img src="{{ $tenant->logoUrl() }}" alt="logo" style="height:48px;width:auto;border-radius:8px;background:#fff;padding:3px;">
                    </div>
                @endif
            </div>
            <div style="display:flex;align-items:end;">
                <button class="btn" type="submit">حفظ الإعدادات</button>
            </div>
        </form>
    </div>

    <div class="card table-wrap">
        <p class="label" style="margin-bottom:0.75rem;">أسعار العملات</p>
        @if ($tenant->price_mode !== 'custom')
            <p class="muted" style="margin:0 0 0.75rem;">الوضع الحالي: أسعار اللوحة. غيّر إلى «أسعاري» لتعديل الشراء والمبيع.</p>
        @endif
        <form method="POST" action="{{ route('admin.office.rates', request()->only('tenant_id')) }}">
            @csrf
            @method('PUT')
            <table>
                <thead>
                <tr>
                    <th>العملة</th>
                    <th>الاسم</th>
                    <th>شراء (لوحة)</th>
                    <th>مبيع (لوحة)</th>
                    <th>شراء (مكتبي)</th>
                    <th>مبيع (مكتبي)</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($panelRates as $i => $rate)
                    @php($override = $overrides->get($rate->currency_code))
                    <tr>
                        <td>
                            <span class="code">{{ $rate->currency_code }}</span>
                            <input type="hidden" name="rates[{{ $i }}][currency_code]" value="{{ $rate->currency_code }}">
                        </td>
                        <td>{{ $rate->currency_name }}</td>
                        <td class="muted" style="font-variant-numeric:tabular-nums;">{{ rtrim(rtrim(number_format((float) $rate->buy_rate, 4, '.', ''), '0'), '.') }}</td>
                        <td class="muted" style="font-variant-numeric:tabular-nums;">{{ rtrim(rtrim(number_format((float) $rate->sell_rate, 4, '.', ''), '0'), '.') }}</td>
                        <td>
                            <input type="number" step="any" min="0" name="rates[{{ $i }}][buy_rate]"
                                   value="{{ old('rates.'.$i.'.buy_rate', $override?->buy_rate ?? $rate->buy_rate) }}"
                                   @disabled($tenant->price_mode !== 'custom')
                                   style="color:var(--up);font-weight:700;">
                        </td>
                        <td>
                            <input type="number" step="any" min="0" name="rates[{{ $i }}][sell_rate]"
                                   value="{{ old('rates.'.$i.'.sell_rate', $override?->sell_rate ?? $rate->sell_rate) }}"
                                   @disabled($tenant->price_mode !== 'custom')
                                   style="color:var(--down);font-weight:700;">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($tenant->price_mode === 'custom')
                <div style="margin-top:1rem;">
                    <button class="btn" type="submit">حفظ أسعاري</button>
                </div>
            @endif
        </form>
    </div>

    <style>
        .office-settings-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
        }
        .office-settings-grid label { display:block; font-size:0.78rem; color:var(--muted); margin-bottom:0.3rem; font-weight:600; }
        select { background:#fff; color:var(--ink); }
    </style>
@endsection
