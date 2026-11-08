<?php

namespace App\Services;

use App\Models\CommercialPolicy;
use App\Models\Promotion;
use App\Models\Reseller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommercialPolicyService
{
    public function forReseller(Reseller $reseller): CommercialPolicy
    {
        $profile = strtoupper(trim((string) $reseller->commercial_profile));
        $policy = CommercialPolicy::query()->where('commercial_profile', $profile)->first();

        if (! $policy) {
            throw (new ModelNotFoundException)->setModel(CommercialPolicy::class, [$profile]);
        }

        return $policy;
    }

    public function activePromotionFor(Reseller $reseller, ?int $profileDiscountPercentage = null): ?Promotion
    {
        $profileDiscountPercentage ??= $this->forReseller($reseller)->discount_percentage;

        return Promotion::query()
            ->where('commercial_profile', strtoupper(trim((string) $reseller->commercial_profile)))
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->get()
            ->filter(fn (Promotion $promotion): bool => $this->effectiveRate($promotion, $profileDiscountPercentage) > $profileDiscountPercentage)
            ->sort(function (Promotion $left, Promotion $right) use ($profileDiscountPercentage): int {
                $leftEffectiveRate = $this->effectiveRate($left, $profileDiscountPercentage);
                $rightEffectiveRate = $this->effectiveRate($right, $profileDiscountPercentage);

                return ($rightEffectiveRate <=> $leftEffectiveRate)
                    ?: ($right->discount_percentage <=> $left->discount_percentage)
                    ?: ($left->getKey() <=> $right->getKey());
            })
            ->first();
    }

    private function effectiveRate(Promotion $promotion, int $profileDiscountPercentage): int
    {
        return $promotion->is_stackable
            ? min(100, $profileDiscountPercentage + $promotion->discount_percentage)
            : max($profileDiscountPercentage, $promotion->discount_percentage);
    }
}
