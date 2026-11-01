<?php

namespace App\Domain\Enum;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Collaborator = 'collaborator';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Collaborator => 'Colaborador',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Administrator;
    }
}
