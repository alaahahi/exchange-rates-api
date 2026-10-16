<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRatesRequest extends FormRequest
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
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.currency_code' => ['required', 'string', 'max:10'],
            'rates.*.buy_rate' => ['required', 'numeric', 'min:0'],
            'rates.*.sell_rate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
