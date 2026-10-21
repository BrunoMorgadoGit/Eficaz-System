<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_can_search_products_by_partial_sku_and_name(): void
    {
        $user = User::factory()->reseller()->create();
        Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Distribuidora Teste',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => '10000.00',
            'is_active' => true,
        ]);

        Product::query()->create([
            'sku' => 'EFI-0000',
            'name' => 'Item de demonstração',
            'price' => '99.90',
            'stock' => 10,
            'is_active' => true,
        ]);

        Product::query()->create([
            'sku' => 'EFI-1001',
            'name' => 'Controlador Industrial X1',
            'price' => '1890.00',
            'stock' => 5,
            'is_active' => true,
        ]);

        Product::query()->create([
            'sku' => 'OUTRO-500',
            'name' => 'Outro Equipamento',
            'price' => '200.00',
            'stock' => 5,
            'is_active' => true,
        ]);

        // Partial match with 'efi' should return both EFI-0000 and EFI-1001
        $response = $this->actingAs($user)->get(route('reseller.products.index', ['sku' => 'efi']));
        $response->assertOk();
        $response->assertSee('EFI-0000');
        $response->assertSee('EFI-1001');
        $response->assertDontSee('OUTRO-500');

        // Partial match with '0000' should return EFI-0000
        $responseDigits = $this->actingAs($user)->get(route('reseller.products.index', ['sku' => '0000']));
        $responseDigits->assertOk();
        $responseDigits->assertSee('EFI-0000');
        $responseDigits->assertDontSee('EFI-1001');

        // Search by name 'Controlador'
        $responseName = $this->actingAs($user)->get(route('reseller.products.index', ['sku' => 'Controlador']));
        $responseName->assertOk();
        $responseName->assertSee('EFI-1001');
        $responseName->assertDontSee('EFI-0000');
    }
}
