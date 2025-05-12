<?php

namespace App\Enums;

enum UserRole: string
{
    case Citizen = 'citizen';
    case Mp = 'mp';
    case Senator = 'senator';
    case Clerk = 'clerk';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Citizen',
            self::Mp => 'Member of Parliament',
            self::Senator => 'Senator',
            self::Clerk => 'Clerk',
            self::Admin => 'Administrator',
        };
    }

    public function isLegislator(): bool
    {
        return \in_array($this, [self::Mp, self::Senator], true);
    }

    public function isClerkish(): bool
    {
        return \in_array($this, [self::Clerk, self::Admin], true);
    }
}
