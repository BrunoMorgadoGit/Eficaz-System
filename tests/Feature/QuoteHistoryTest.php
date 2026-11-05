<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_sees_only_its_own_quotes_and_can_open_the_details(): void
    {
        [$user, $reseller] = $this->makeReseller('revendedor-a@example.test');
        [, $otherReseller] = $this->makeReseller('revendedor-b@example.test');
        $quote = $this->makeQuote($reseller);
        $otherQuote = $this->makeQuote($otherReseller);

        $this->actingAs($user)
            ->get(route('reseller.quotes.index'))
            ->assertOk()
            ->assertSee('Histórico de Orçamentos')
            ->assertSee($quote->reference())
            ->assertSee(route('reseller.quotes.show', $quote), false)
            ->assertDontSee($otherQuote->reference());
    }

    public function test_quote_history_requires_an_active_reseller_account(): void
    {
        $this->get(route('reseller.quotes.index'))
            ->assertRedirect(route('login'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('reseller.quotes.index'))
            ->assertForbidden();

        [$user] = $this->makeReseller('inativo@example.test', isActive: false);
        $this->actingAs($user)
            ->get(route('reseller.quotes.index'))
            ->assertForbidden();
    }

    public function test_quote_history_displays_a_clear_empty_state(): void
    {
        [$user] = $this->makeReseller('vazio@example.test');

        $this->actingAs($user)
            ->get(route('reseller.quotes.index'))
            ->assertOk()
            ->assertSee('Nenhum orçamento registrado ainda.')
            ->assertSee(route('reseller.products.index'), false);
    }

    /** @return array{0: User, 1: Reseller} */
    private function makeReseller(string $email, bool $isActive = true): array
    {
        $user = User::factory()->reseller()->create(['email' => $email]);
        $reseller = Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Distribuidora de Teste Ltda.',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => null,
            'is_active' => $isActive,
        ]);

        return [$user, $reseller];
    }

    private function makeQuote(Reseller $reseller): Quote
    {
        return Quote::query()->create([
            'reseller_id' => $reseller->id,
            'subtotal' => '25.00',
            'discount' => '0.00',
            'total' => '25.00',
        ]);
    }
}
