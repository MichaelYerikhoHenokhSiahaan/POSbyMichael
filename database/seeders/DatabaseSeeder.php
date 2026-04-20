<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'MYHS'],
            [
                'name' => 'UD PINDO Admin',
                'email' => 'myhs@udpindo.local',
                'password' => 'udindo123',
                'role' => User::ROLE_DEVELOPER,
            ],
        );

        $beverages = Category::query()->create([
            'name' => 'Beverages',
            'description' => 'Soft drinks, bottled water, and ready-to-drink items.',
        ]);

        $snacks = Category::query()->create([
            'name' => 'Snacks',
            'description' => 'Light food items and packaged snacks.',
        ]);

        $household = Category::query()->create([
            'name' => 'Household',
            'description' => 'Daily-use household essentials.',
        ]);

        Product::query()->create([
            'category_id' => $beverages->id,
            'sku' => 'DRK-001',
            'name' => 'Mineral Water 600ml',
            'price' => 5000,
            'stock' => 80,
            'unit' => 'bottle',
        ]);

        Product::query()->create([
            'category_id' => $beverages->id,
            'sku' => 'DRK-002',
            'name' => 'Orange Juice',
            'price' => 9000,
            'stock' => 36,
            'unit' => 'bottle',
        ]);

        Product::query()->create([
            'category_id' => $snacks->id,
            'sku' => 'SNK-001',
            'name' => 'Potato Chips',
            'price' => 12500,
            'stock' => 28,
            'unit' => 'pack',
        ]);

        Product::query()->create([
            'category_id' => $snacks->id,
            'sku' => 'SNK-002',
            'name' => 'Chocolate Wafer',
            'price' => 7500,
            'stock' => 45,
            'unit' => 'pack',
        ]);

        Product::query()->create([
            'category_id' => $household->id,
            'sku' => 'HSE-001',
            'name' => 'Laundry Detergent',
            'price' => 32000,
            'stock' => 18,
            'unit' => 'pouch',
        ]);

        Customer::query()->create([
            'name' => 'Walk-in Member',
            'email' => 'member@udpd.test',
            'phone' => '081234567890',
            'address' => 'Main Store Area',
        ]);
    }
}
