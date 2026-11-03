<?php

namespace Tests\Unit;

use App\Models\Reseller;
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

    #[DataProvider('commercialProfiles')]
    public function test_reseller_discount_percentage_matches_the_mvp_profiles(
        string $profile,
        int $expectedPercentage,
    ): void {
        $reseller = new Reseller(['commercial_profile' => $profile]);

        $this->assertSame($expectedPercentage, $reseller->discountPercentage());
    }

    public static function commercialProfiles(): array
    {
        return [
            'standard' => [Reseller::PROFILE_STANDARD, 0],
            'gold' => [Reseller::PROFILE_GOLD, 5],
            'premium' => [Reseller::PROFILE_PREMIUM, 10],
            'unknown profile keeps the default rate' => ['SILVER', 0],
        ];
    }
}
