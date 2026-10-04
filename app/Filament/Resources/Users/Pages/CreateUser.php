<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\TenantRoles;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public string $assignedRole = 'staff';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $role = (string) ($this->data['role'] ?? 'staff');
        $this->assignedRole = in_array($role, ['owner', 'staff', 'receptionist'], true) ? $role : 'staff';

        unset($data['role']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record instanceof User) {
            TenantRoles::assign($record, $this->assignedRole);
        }
    }
}
