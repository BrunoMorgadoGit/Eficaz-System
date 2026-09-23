<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@eficaz.test',
        ], [
            'name' => 'Administrador Eficaz',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
        ]);

        $resellerUser = User::updateOrCreate([
            'email' => 'revendedor@eficaz.test',
        ], [
            'name' => 'Marina Costa',
            'role' => User::ROLE_REVENDEDOR,
            'password' => Hash::make('password'),
        ]);

        Reseller::updateOrCreate([
            'user_id' => $resellerUser->id,
        ], [
            'company_name' => 'Costa Distribuidora Ltda.',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'credit_limit' => 25000,
            'is_active' => true,
        ]);

        foreach ([
            [
                'sku' => 'EFI-1001',
                'name' => 'Controlador Industrial X1',
                'description' => 'Controlador para automação de linhas produtivas.',
                'price' => 1890.00,
                'stock' => 24,
            ],
            [
                'sku' => 'EFI-1002',
                'name' => 'Sensor Óptico S20',
                'description' => 'Sensor fotoelétrico para leitura de presença.',
                'price' => 485.50,
                'stock' => 60,
            ],
            [
                'sku' => 'EFI-1003',
                'name' => 'Fonte Industrial 24V',
                'description' => 'Fonte de alimentação para painéis elétricos.',
                'price' => 320.90,
                'stock' => 40,
            ],
            [
                'sku' => 'EFI-1004',
                'name' => 'Módulo de Comunicação',
                'description' => 'Módulo de comunicação para equipamentos industriais.',
                'price' => 749.00,
                'stock' => 18,
            ],
            [
                'sku' => 'EFI-0000',
                'name' => 'Item de demonstração indisponível',
                'description' => 'Produto intencionalmente sem estoque para validar o fluxo.',
                'price' => 99.90,
                'stock' => 0,
            ],
        ] as $product) {
            Product::updateOrCreate([
                'sku' => $product['sku'],
            ], $product + ['is_active' => true]);
        }
    }
}
