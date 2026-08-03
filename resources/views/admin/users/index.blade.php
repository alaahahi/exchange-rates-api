@extends('admin.layouts.app')

@section('title', 'المستخدمون')

@section('content')
    <h1 class="page-title">المستخدمون</h1>
    <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem;">إدارة حسابات الدخول وتعديل كلمات المرور.</p>

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
        <p class="label" style="margin-bottom:0.75rem;">إضافة مستخدم</p>
        <form method="POST" action="{{ route('admin.users.store') }}" class="create-user-grid">
            @csrf
            <div>
                <label for="new-name">الاسم</label>
                <input id="new-name" type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label for="new-email">البريد</label>
                <input id="new-email" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label for="new-password">كلمة المرور</label>
                <input id="new-password" type="password" name="password" required autocomplete="new-password">
            </div>
            <div>
                <label for="new-password-confirmation">تأكيد المرور</label>
                <input id="new-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <div style="display:flex;align-items:end;">
                <button class="btn" type="submit">إضافة</button>
            </div>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>الاسم / البريد</th>
                <th>كلمة مرور جديدة</th>
                <th>إجراءات</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                @php($profileForm = 'user-profile-'.$user->id)
                @php($passwordForm = 'user-password-'.$user->id)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td style="min-width:14rem;">
                        <input form="{{ $profileForm }}" type="text" name="name" value="{{ old('name', $user->name) }}" required style="margin-bottom:0.4rem;">
                        <input form="{{ $profileForm }}" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @if ((int) $user->id === (int) auth()->id())
                            <div class="muted" style="margin-top:0.35rem;font-size:0.75rem;">حسابك الحالي</div>
                        @endif
                    </td>
                    <td style="min-width:12rem;">
                        <input form="{{ $passwordForm }}" type="password" name="password" placeholder="كلمة مرور جديدة" required autocomplete="new-password" style="margin-bottom:0.4rem;">
                        <input form="{{ $passwordForm }}" type="password" name="password_confirmation" placeholder="تأكيد" required autocomplete="new-password">
                    </td>
                    <td>
                        <div class="row-actions" style="flex-wrap:wrap;">
                            <form id="{{ $profileForm }}" method="POST" action="{{ route('admin.users.update', $user) }}">
                                @csrf
                                @method('PUT')
                                <button class="btn" type="submit">حفظ البيانات</button>
                            </form>
                            <form id="{{ $passwordForm }}" method="POST" action="{{ route('admin.users.password', $user) }}">
                                @csrf
                                @method('PUT')
                                <button class="btn btn-ghost" type="submit">تغيير المرور</button>
                            </form>
                            @if ((int) $user->id !== (int) auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('حذف هذا المستخدم؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost" type="submit" style="color:var(--down);border-color:#f5c2c0;">حذف</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <style>
        .create-user-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1fr;
        }
        @media (min-width: 800px) {
            .create-user-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
        .create-user-grid label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
        }
    </style>
@endsection
