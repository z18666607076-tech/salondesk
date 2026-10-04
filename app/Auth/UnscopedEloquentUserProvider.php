<?php

namespace App\Auth;

use App\Models\Scopes\TenantScope;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Authentication must load a user even when another tenant is current.
 * The tenant middleware then rejects a token that belongs to a different salon.
 */
class UnscopedEloquentUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->withoutGlobalScope(TenantScope::class);
    }
}
