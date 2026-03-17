<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Finance = 'finance';
    case Manager = 'manager';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            UserRole::Admin => 'Admin',
            UserRole::Finance => 'Finance',
            UserRole::Manager => 'Manager',
            UserRole::Staff => 'Staff',
        };
    }
}
