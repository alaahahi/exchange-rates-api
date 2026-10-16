<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:tenants,slug'],
            'price_mode' => ['required', Rule::in([Tenant::PRICE_PANEL, Tenant::PRICE_CUSTOM])],
            'is_active' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'required_with:email', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المكتب مطلوب',
            'slug.unique' => 'الرابط مستخدم مسبقاً',
            'email.unique' => 'البريد مستخدم مسبقاً',
            'password.required_with' => 'كلمة المرور مطلوبة مع البريد',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'logo.image' => 'الملف يجب أن يكون صورة',
        ];
    }
}
