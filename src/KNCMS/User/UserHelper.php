<?php
declare(strict_types=1);

namespace KNCMS\User;

final class UserHelper
{
    public static function capbac(int $level): string
    {
        return match ($level) {
            1 => 'Thành Viên',
            2 => 'Nhân viên',
            default => 'Admin',
        };
    }
}
