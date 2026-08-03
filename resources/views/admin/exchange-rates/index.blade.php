@extends('admin.layouts.app')

@section('title', 'أسعار الصرف')

@section('content')
    <h1 class="page-title">أسعار الصرف</h1>
    <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem;">الأسعار تُسحب من قمر الفجر تلقائياً كل دقيقتين. التعديل اليدوي ممكن ويُستبدل عند المزامنة التالية.</p>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>العملة</th>
                <th>الاسم</th>
                <th>شراء</th>
                <th>مبيع</th>
                <th>التغيّر %</th>
                <th>المصدر</th>
                <th>ترتيب</th>
                <th>نشط</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rates as $rate)
                @php($formId = 'exchange-'.$rate->id)
                <tr>
                    <td><span class="code">{{ $rate->currency_code }}</span></td>
                    <td><input form="{{ $formId }}" type="text" name="currency_name" value="{{ old('currency_name', $rate->currency_name) }}" required></td>
                    <td><input form="{{ $formId }}" type="number" step="any" min="0" name="buy_rate" value="{{ old('buy_rate', $rate->buy_rate) }}" required style="color:var(--up);font-weight:700;"></td>
                    <td><input form="{{ $formId }}" type="number" step="any" min="0" name="sell_rate" value="{{ old('sell_rate', $rate->sell_rate) }}" required style="color:var(--down);font-weight:700;"></td>
                    <td><input form="{{ $formId }}" type="number" step="any" name="change_percentage" value="{{ old('change_percentage', $rate->change_percentage) }}"></td>
                    <td><span class="muted" style="font-size:0.78rem;">{{ $rate->source ?: '—' }}</span></td>
                    <td><input form="{{ $formId }}" type="number" name="sort_order" value="{{ old('sort_order', $rate->sort_order) }}" required style="min-width:4rem;"></td>
                    <td>
                        <label class="check">
                            <input form="{{ $formId }}" type="checkbox" name="is_active" value="1" @checked(old('is_active', $rate->is_active))>
                            ظاهر
                        </label>
                    </td>
                    <td>
                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.exchange-rates.update', $rate) }}">
                            @csrf
                            @method('PUT')
                            <button class="btn" type="submit">حفظ</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
