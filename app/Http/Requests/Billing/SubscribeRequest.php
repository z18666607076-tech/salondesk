<?php

namespace App\Http\Requests\Billing;

use App\Enums\Plan;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = app(TenantContext::class)->get();
        $user = $this->user();

        return $user !== null
            && $tenant instanceof Tenant
            && $user->can('manageBilling', $tenant);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::enum(Plan::class)],
        ];
    }
}
