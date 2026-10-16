@extends('admin.layouts.app')

@section('title', 'المكاتب')

@section('content')
    <h1 class="page-title">المكاتب</h1>
    <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem;">كل مكتب يعرض نفس الموقع مع لوغوه وأسعاره عبر رابط خاص.</p>

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
        <p class="label" style="margin-bottom:0.75rem;">إضافة مكتب</p>
        <form method="POST" action="{{ route('admin.tenants.store') }}" enctype="multipart/form-data" class="create-tenant-grid">
            @csrf
            <div>
                <label for="new-name">اسم المكتب</label>
                <input id="new-name" type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label for="new-slug">الرابط (slug)</label>
                <input id="new-slug" type="text" name="slug" value="{{ old('slug') }}" placeholder="office-name" dir="ltr">
            </div>
            <div>
                <label for="new-price-mode">مصدر السعر</label>
                <select id="new-price-mode" name="price_mode" style="width:100%;padding:0.45rem 0.55rem;border:1px solid var(--line);border-radius:0.65rem;font:inherit;">
                    <option value="panel" @selected(old('price_mode', 'panel') === 'panel')>أسعار لوحة dinar-now</option>
                    <option value="custom" @selected(old('price_mode') === 'custom')>أسعار المكتب</option>
                </select>
            </div>
            <div>
                <label for="new-logo">اللوغو</label>
                <input id="new-logo" type="file" name="logo" accept="image/*">
            </div>
            <div>
                <label for="new-email">بريد الدخول (اختياري)</label>
                <input id="new-email" type="email" name="email" value="{{ old('email') }}" dir="ltr">
            </div>
            <div>
                <label for="new-password">كلمة المرور</label>
                <input id="new-password" type="password" name="password" autocomplete="new-password">
            </div>
            <div>
                <label for="new-password-confirmation">تأكيد المرور</label>
                <input id="new-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password">
            </div>
            <div style="display:flex;align-items:end;gap:0.75rem;">
                <label class="check">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                    نشط
                </label>
                <button class="btn" type="submit">إضافة</button>
            </div>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>المكتب</th>
                <th>الرابط</th>
                <th>السعر</th>
                <th>اللوغو</th>
                <th>حالة</th>
                <th>إجراءات</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($tenants as $tenant)
                @php($formId = 'tenant-'.$tenant->id)
                <tr>
                    <td style="min-width:10rem;">
                        <input form="{{ $formId }}" type="text" name="name" value="{{ old('name', $tenant->name) }}" required>
                    </td>
                    <td style="min-width:9rem;">
                        <input form="{{ $formId }}" type="text" name="slug" value="{{ old('slug', $tenant->slug) }}" required dir="ltr">
                        <div class="muted" style="margin-top:0.35rem;font-size:0.75rem;" dir="ltr">
                            <a href="{{ $frontendUrl }}/o/{{ $tenant->slug }}" target="_blank" rel="noopener">{{ $frontendUrl }}/o/{{ $tenant->slug }}</a>
                        </div>
                    </td>
                    <td>
                        <select form="{{ $formId }}" name="price_mode" style="width:100%;padding:0.45rem 0.55rem;border:1px solid var(--line);border-radius:0.65rem;font:inherit;">
                            <option value="panel" @selected(old('price_mode', $tenant->price_mode) === 'panel')>لوحة</option>
                            <option value="custom" @selected(old('price_mode', $tenant->price_mode) === 'custom')>مخصص</option>
                        </select>
                    </td>
                    <td>
                        @if ($tenant->logo_path)
                            <img src="{{ $tenant->logoUrl() }}" alt="" style="height:36px;width:auto;border-radius:6px;background:#fff;padding:2px;">
                        @endif
                        <input form="{{ $formId }}" type="file" name="logo" accept="image/*" style="margin-top:0.35rem;">
                    </td>
                    <td>
                        <label class="check">
                            <input form="{{ $formId }}" type="checkbox" name="is_active" value="1" @checked(old('is_active', $tenant->is_active))>
                            نشط
                        </label>
                    </td>
                    <td>
                        <div class="row-actions" style="flex-wrap:wrap;">
                            <form id="{{ $formId }}" method="POST" action="{{ route('admin.tenants.update', $tenant) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <button class="btn" type="submit">حفظ</button>
                            </form>
                            <a class="btn btn-ghost" href="{{ route('admin.office.edit', ['tenant_id' => $tenant->id]) }}">أسعار</a>
                            <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" onsubmit="return confirm('حذف هذا المكتب؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost" type="submit" style="color:var(--down);border-color:#f5c2c0;">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">لا توجد مكاتب بعد.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <style>
        .create-tenant-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
        }
        .create-tenant-grid label { display:block; font-size:0.78rem; color:var(--muted); margin-bottom:0.3rem; font-weight:600; }
        select { background:#fff; color:var(--ink); }
    </style>
@endsection
