<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccessRoleRulesTest extends TestCase
{
    #[DataProvider('userRoles')]
    public function test_user_role_helpers_match_only_their_assigned_role(
        string $role,
        bool $isAdmin,
        bool $isReseller,
    ): void {
        $user = new User(['role' => $role]);

        $this->assertSame($isAdmin, $user->isAdmin());
        $this->assertSame($isReseller, $user->isReseller());
    }

    public static function userRoles(): array
    {
        return [
            'administrator' => [User::ROLE_ADMIN, true, false],
            'reseller' => [User::ROLE_REVENDEDOR, false, true],
            'unsupported role' => ['OPERADOR', false, false],
        ];
    }
}
