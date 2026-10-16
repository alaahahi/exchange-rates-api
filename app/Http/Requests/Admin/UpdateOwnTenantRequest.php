<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isPlatformAdmin() || ($user->isTenantAdmin() && $user->tenant_id));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'price_mode' => ['required', Rule::in([Tenant::PRICE_PANEL, Tenant::PRICE_CUSTOM])],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
