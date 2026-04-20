<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\GasTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TransactionRecord;
use App\Models\User;
use App\Models\WarehouseStockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->user = User::query()
            ->where('username', 'MYHS')
            ->firstOrFail();
    }

    public function test_dashboard_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Point of Sale Management');
        $response->assertSee('developer');
    }

    public function test_admin_cannot_access_dashboard(): void
    {
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($adminUser)->get(route('dashboard'));

        $response->assertRedirect(route('sales.index'));
    }

    public function test_root_route_redirects_to_sales_page(): void
    {
        $response = $this->actingAs($this->user)->get('/');

        $response->assertRedirect(route('sales.index'));
    }

    public function test_dashboard_low_stock_alert_uses_five_item_pagination(): void
    {
        foreach (range(1, 6) as $number) {
            Product::create([
                'sku' => 'LOW-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'name' => "Low Stock Product {$number}",
                'price' => 1000 * $number,
                'stock' => $number,
                'unit' => 'pcs',
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Low stock alert');
        $response->assertSee('Showing 1-5 of 6 products');

        $secondPageResponse = $this->actingAs($this->user)->get(route('dashboard', [
            'low_stock_page' => 2,
        ]));

        $secondPageResponse->assertOk();
        $secondPageResponse->assertSee('Showing 6-6 of 6 products');
    }

    public function test_dashboard_recent_sales_uses_five_item_pagination(): void
    {
        $customer = Customer::create([
            'name' => 'Dashboard Customer',
            'email' => 'dashboard@example.com',
        ]);

        foreach (range(1, 6) as $number) {
            Sale::create([
                'invoice_number' => "INV-DASH-{$number}",
                'customer_id' => $customer->id,
                'cashier_name' => 'Admin',
                'payment_method' => 'cash',
                'subtotal' => 1000 * $number,
                'discount' => 0,
                'total' => 1000 * $number,
                'sold_at' => now()->subMinutes($number),
                'notes' => null,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Recent sales');
        $response->assertSee('Showing 1-5 of 6 sales');

        $secondPageResponse = $this->actingAs($this->user)->get(route('dashboard', [
            'recent_sales_page' => 2,
        ]));

        $secondPageResponse->assertOk();
        $secondPageResponse->assertSee('Showing 6-6 of 6 sales');
    }

    public function test_product_can_be_created_from_inventory_form(): void
    {
        $category = Category::create([
            'name' => 'Stationery',
            'description' => 'Office and school supplies',
        ]);

        $response = $this->actingAs($this->user)->post(route('products.store'), [
            'category_id' => $category->id,
            'sku' => 'STY-001',
            'name' => 'Notebook',
            'price' => 15000,
            'stock' => 20,
            'unit' => 'pcs',
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'STY-001',
            'name' => 'Notebook',
            'stock' => 20,
        ]);
    }

    public function test_inventory_can_be_exported_to_excel(): void
    {
        $matchingCategory = Category::create([
            'name' => 'Export Inventory Category',
            'description' => 'Inventory export',
        ]);

        $otherCategory = Category::create([
            'name' => 'Other Inventory Category',
            'description' => 'Other inventory export',
        ]);

        Product::create([
            'category_id' => $matchingCategory->id,
            'sku' => 'INV-EXP-001',
            'name' => 'Inventory Export Match',
            'price' => 10000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        Product::create([
            'category_id' => $otherCategory->id,
            'sku' => 'INV-EXP-002',
            'name' => 'Inventory Other Product',
            'price' => 12000,
            'stock' => 7,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)->get(route('products.export', [
            'search' => 'Match',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertSee('Inventory Export');
        $response->assertSee('Inventory Export Match');
        $response->assertDontSee('Inventory Other Product');
        $response->assertSee('INV-EXP-001');
    }

    public function test_sale_can_be_completed_and_stock_is_reduced(): void
    {
        $category = Category::create([
            'name' => 'Beverages',
            'description' => 'Ready to drink items',
        ]);

        $customer = Customer::create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'DRK-100',
            'name' => 'Sparkling Water',
            'price' => 12000,
            'stock' => 10,
            'unit' => 'bottle',
        ]);

        $response = $this->actingAs($this->user)->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'customer_payment' => 25000,
            'discount' => 2000,
            'notes' => 'Paid in full',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect();

        $sale = Sale::query()->latest('id')->first();
        $this->assertNotNull($sale);
        $this->assertMatchesRegularExpression('/^INV-\d{14}-\d{3}$/', $sale->invoice_number);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 24000,
            'discount' => 2000,
            'total' => 22000,
            'customer_payment' => 25000,
            'change_amount' => 3000,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'line_total' => 24000,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseMissing('warehouse_stock_movements', [
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'type' => WarehouseStockMovement::TYPE_REMOVE,
            'quantity' => 2,
        ]);
    }

    public function test_receipt_page_can_be_rendered_for_a_sale(): void
    {
        $category = Category::create([
            'name' => 'Snacks',
            'description' => 'Packaged food',
        ]);

        $customer = Customer::create([
            'name' => 'Receipt Customer',
            'email' => 'receipt@example.com',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SNK-500',
            'name' => 'Cookie Pack',
            'price' => 10000,
            'stock' => 15,
            'unit' => 'pack',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-500',
            'customer_id' => $customer->id,
            'cashier_name' => 'Admin',
            'payment_method' => 'cash',
            'subtotal' => 20000,
            'discount' => 1000,
            'total' => 19000,
            'customer_payment' => 20000,
            'change_amount' => 1000,
            'sold_at' => now(),
            'notes' => 'Receipt ready',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'line_total' => 20000,
        ]);

        $response = $this->actingAs($this->user)->get(route('sales.receipt', $sale));

        $response->assertOk();
        $response->assertSee('Transaction Receipt');
        $response->assertSee('INV-TEST-500');
        $response->assertSee('Cookie Pack');
        $response->assertSee('Customer payment');
        $response->assertSee('Change');
    }

    public function test_receipt_auto_print_mode_includes_direct_print_script(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-AUTO-PRINT',
            'customer_id' => null,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'total' => 10000,
            'customer_payment' => 10000,
            'change_amount' => 0,
            'sold_at' => now(),
            'notes' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('sales.receipt', [
            'sale' => $sale,
            'auto_print' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('window.print()', false);
        $response->assertSee('window.close()', false);
    }

    public function test_warehouse_invoice_can_be_created_for_gudang_products(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 15, 9, 30, 0, 'Asia/Jakarta'));

        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $customer = Customer::create([
            'name' => 'Gudang Customer',
            'email' => 'gudang@example.com',
        ]);

        $product = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-INV-001',
            'name' => 'Beras Gudang',
            'price' => 75000,
            'stock' => 10,
            'unit' => 'sak',
        ]);

        $response = $this->actingAs($this->user)->post(route('warehouse-invoices.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'payment_status' => 'Belum Lunas',
            'customer_payment' => 160000,
            'notes' => 'Invoice gudang pertama',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $sale = Sale::query()->latest('id')->first();
        $this->assertNotNull($sale);

        $response->assertRedirect(route('warehouse-invoices.show', $sale));
        $showResponse = $this->actingAs($this->user)->get(route('warehouse-invoices.show', $sale));
        $showResponse->assertOk();
        $showResponse->assertSee('Invoice Gudang');
        $showResponse->assertSee('INV.0415.0001');
        $this->assertSame('INV.0415.0001', $sale->invoice_number);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'invoice_number' => 'INV.0415.0001',
            'customer_id' => $customer->id,
            'subtotal' => 150000,
            'total' => 150000,
            'customer_payment' => 160000,
            'change_amount' => 10000,
            'payment_status' => 'Belum Lunas',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseHas('warehouse_stock_movements', [
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'type' => WarehouseStockMovement::TYPE_REMOVE,
            'quantity' => 2,
            'notes' => 'INV.0415.0001',
        ]);

        Carbon::setTestNow();
    }

    public function test_warehouse_invoice_can_be_created_for_non_gudang_products(): void
    {
        $retailCategory = Category::create([
            'name' => 'Retail',
            'description' => 'Produk retail',
        ]);

        $retailProduct = Product::create([
            'category_id' => $retailCategory->id,
            'sku' => 'RTL-INV-001',
            'name' => 'Produk Retail',
            'price' => 25000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('warehouse-invoices.store'), [
                'payment_method' => 'cash',
                'payment_status' => 'Lunas',
                'customer_payment' => 50000,
                'items' => [
                    ['product_id' => $retailProduct->id, 'quantity' => 1],
                ],
            ]);

        $sale = Sale::query()->latest('id')->first();
        $this->assertNotNull($sale);

        $response->assertRedirect(route('warehouse-invoices.show', $sale));

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'subtotal' => 25000,
            'total' => 25000,
            'payment_status' => 'Lunas',
        ]);

        $this->assertDatabaseHas('warehouse_stock_movements', [
            'product_id' => $retailProduct->id,
            'user_id' => $this->user->id,
            'type' => WarehouseStockMovement::TYPE_REMOVE,
            'quantity' => 1,
            'notes' => $sale->invoice_number,
        ]);
    }

    public function test_warehouse_invoice_receipt_is_in_bahasa_indonesia_and_landscape(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $customer = Customer::create([
            'name' => 'Receipt Gudang Customer',
            'email' => 'receipt-gudang@example.com',
        ]);

        $product = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-RCP-001',
            'name' => 'Minyak Gudang',
            'price' => 20000,
            'stock' => 8,
            'unit' => 'dus',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV.0415.0002',
            'customer_id' => $customer->id,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 40000,
            'discount' => 0,
            'total' => 40000,
            'customer_payment' => 50000,
            'change_amount' => 10000,
            'payment_status' => 'Lunas',
            'sold_at' => now(),
            'notes' => 'Invoice gudang cetak',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20000,
            'line_total' => 40000,
        ]);

        $response = $this->actingAs($this->user)->get(route('warehouse-invoices.receipt', $sale));

        $response->assertOk();
        $response->assertSee('Invoice Penjualan Gudang');
        $response->assertSee('INV.0415.0002');
        $response->assertSee('Pembayaran pelanggan');
        $response->assertSee('Yang Menerima');
        $response->assertSee('Hormat Kami');
        $response->assertSee('size: A4 landscape;', false);
    }

    public function test_warehouse_invoice_payment_status_is_saved_manually(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 16, 9, 30, 0, 'Asia/Jakarta'));

        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $product = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-INV-STATUS',
            'name' => 'Status Gudang',
            'price' => 50000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)->post(route('warehouse-invoices.store'), [
            'payment_method' => 'cash',
            'payment_status' => 'Belum Lunas',
            'customer_payment' => 50000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $sale = Sale::query()->latest('id')->first();

        $response->assertRedirect(route('warehouse-invoices.show', $sale));
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'invoice_number' => 'INV.0416.0001',
            'payment_status' => 'Belum Lunas',
        ]);

        $showResponse = $this->actingAs($this->user)->get(route('warehouse-invoices.show', $sale));
        $showResponse->assertOk();
        $showResponse->assertSee('Belum Lunas');

        Carbon::setTestNow();
    }

    public function test_warehouse_invoice_payment_status_can_be_edited(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $product = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-INV-EDIT',
            'name' => 'Edit Status Gudang',
            'price' => 40000,
            'stock' => 6,
            'unit' => 'pcs',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV.0416.0002',
            'customer_id' => null,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 40000,
            'discount' => 0,
            'total' => 40000,
            'customer_payment' => 40000,
            'change_amount' => 0,
            'payment_status' => 'Belum Lunas',
            'sold_at' => now(),
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 40000,
            'line_total' => 40000,
        ]);

        $response = $this->actingAs($this->user)->patch(route('warehouse-invoices.update-payment-status', $sale), [
            'payment_status' => 'Lunas',
        ]);

        $response->assertRedirect(route('warehouse-invoices.show', $sale));
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'payment_status' => 'Lunas',
        ]);

        $showResponse = $this->actingAs($this->user)->get(route('warehouse-invoices.show', $sale));
        $showResponse->assertOk();
        $showResponse->assertSee('Simpan status');
        $showResponse->assertSee('Lunas');
    }

    public function test_gas_menu_creates_default_gas_catalog(): void
    {
        $response = $this->actingAs($this->user)->get(route('gas.index'));

        $response->assertOk();
        $response->assertSee('Input stok Gas');
        $response->assertSee('Jual Gas');

        $gasCategory = Category::query()->where('name', 'Gas')->first();
        $this->assertNotNull($gasCategory);

        $this->assertDatabaseHas('products', [
            'category_id' => $gasCategory->id,
            'name' => 'Isi Gas',
            'price' => 20000,
        ]);

        $this->assertDatabaseHas('products', [
            'category_id' => $gasCategory->id,
            'name' => 'Gas Kosong',
            'price' => 180000,
        ]);

        $this->assertDatabaseHas('products', [
            'category_id' => $gasCategory->id,
            'name' => 'Gas + Isi',
            'price' => 210000,
        ]);
    }

    public function test_gas_input_only_increases_stock_and_records_transaction(): void
    {
        $this->actingAs($this->user)->get(route('gas.index'));
        $isiGas = Product::query()->where('name', 'Isi Gas')->firstOrFail();
        $gasPlusIsi = Product::query()->where('name', 'Gas + Isi')->firstOrFail();

        $response = $this->actingAs($this->user)->post(route('gas.input.store'), [
            'product_id' => $isiGas->id,
            'quantity' => 5,
            'notes' => 'Stok baru datang',
        ]);

        $response->assertRedirect(route('gas.index'));
        $this->assertDatabaseHas('products', [
            'id' => $isiGas->id,
            'stock' => 5,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $gasPlusIsi->id,
            'stock' => 5,
        ]);

        $this->assertDatabaseHas('gas_transactions', [
            'product_id' => $isiGas->id,
            'user_id' => $this->user->id,
            'type' => GasTransaction::TYPE_INPUT,
            'quantity' => 5,
            'total' => 0,
            'stock_effect' => 'Isi Gas +5, Gas + Isi +5',
        ]);
    }

    public function test_isi_gas_sale_uses_discount_price_and_updates_related_stock(): void
    {
        $this->actingAs($this->user)->get(route('gas.index'));

        $isiGas = Product::query()->where('name', 'Isi Gas')->firstOrFail();
        $gasKosong = Product::query()->where('name', 'Gas Kosong')->firstOrFail();
        $gasPlusIsi = Product::query()->where('name', 'Gas + Isi')->firstOrFail();

        $isiGas->update(['stock' => 10]);
        $gasKosong->update(['stock' => 4]);
        $gasPlusIsi->update(['stock' => 10]);

        $response = $this->actingAs($this->user)->post(route('gas.sale.store'), [
            'product_id' => $isiGas->id,
            'quantity' => 3,
            'use_discount' => '1',
            'payment_method' => 'cash',
            'notes' => 'Penjualan isi gas',
        ]);

        $response->assertRedirect(route('gas.index'));

        $this->assertDatabaseHas('products', [
            'id' => $isiGas->id,
            'stock' => 7,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $gasPlusIsi->id,
            'stock' => 7,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $gasKosong->id,
            'stock' => 7,
        ]);

        $this->assertDatabaseHas('gas_transactions', [
            'product_id' => $isiGas->id,
            'type' => GasTransaction::TYPE_SALE,
            'quantity' => 3,
            'unit_price' => 19000,
            'total' => 57000,
            'discount_applied' => true,
            'stock_effect' => 'Isi Gas -3, Gas + Isi -3, Gas Kosong +3',
        ]);
    }

    public function test_isi_gas_sale_uses_large_quantity_discount_over_99(): void
    {
        $this->actingAs($this->user)->get(route('gas.index'));

        $isiGas = Product::query()->where('name', 'Isi Gas')->firstOrFail();
        $gasPlusIsi = Product::query()->where('name', 'Gas + Isi')->firstOrFail();

        $isiGas->update(['stock' => 150]);
        $gasPlusIsi->update(['stock' => 150]);

        $response = $this->actingAs($this->user)->post(route('gas.sale.store'), [
            'product_id' => $isiGas->id,
            'quantity' => 100,
            'use_discount' => '1',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('gas.index'));

        $this->assertDatabaseHas('gas_transactions', [
            'product_id' => $isiGas->id,
            'type' => GasTransaction::TYPE_SALE,
            'quantity' => 100,
            'unit_price' => 18500,
            'total' => 1850000,
        ]);
    }

    public function test_gas_plus_isi_sale_decreases_isi_gas_stock(): void
    {
        $this->actingAs($this->user)->get(route('gas.index'));

        $isiGas = Product::query()->where('name', 'Isi Gas')->firstOrFail();
        $gasPlusIsi = Product::query()->where('name', 'Gas + Isi')->firstOrFail();

        $isiGas->update(['stock' => 8]);
        $gasPlusIsi->update(['stock' => 2]);

        $response = $this->actingAs($this->user)->post(route('gas.sale.store'), [
            'product_id' => $gasPlusIsi->id,
            'quantity' => 2,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('gas.index'));

        $this->assertDatabaseHas('products', [
            'id' => $isiGas->id,
            'stock' => 6,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $gasPlusIsi->id,
            'stock' => 0,
        ]);

        $this->assertDatabaseHas('gas_transactions', [
            'product_id' => $gasPlusIsi->id,
            'type' => GasTransaction::TYPE_SALE,
            'quantity' => 2,
            'unit_price' => 210000,
            'total' => 420000,
            'stock_effect' => 'Gas + Isi -2, Isi Gas -2',
        ]);
    }

    public function test_gas_kosong_sale_does_not_change_stock(): void
    {
        $this->actingAs($this->user)->get(route('gas.index'));

        $gasKosong = Product::query()->where('name', 'Gas Kosong')->firstOrFail();
        $gasKosong->update(['stock' => 9]);

        $response = $this->actingAs($this->user)->post(route('gas.sale.store'), [
            'product_id' => $gasKosong->id,
            'quantity' => 2,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('gas.index'));

        $this->assertDatabaseHas('products', [
            'id' => $gasKosong->id,
            'stock' => 9,
        ]);

        $this->assertDatabaseHas('gas_transactions', [
            'product_id' => $gasKosong->id,
            'type' => GasTransaction::TYPE_SALE,
            'quantity' => 2,
            'unit_price' => 180000,
            'total' => 360000,
            'stock_effect' => 'Tidak ada perubahan stok.',
        ]);
    }

    public function test_sales_page_shows_logged_in_cashier_and_recent_transactions_pagination(): void
    {
        $category = Category::create([
            'name' => 'Paged Category',
            'description' => 'Pagination test',
        ]);

        $customer = Customer::create([
            'name' => 'Paged Customer',
            'email' => 'paged@example.com',
        ]);

        foreach (range(1, 6) as $number) {
            $product = Product::create([
                'category_id' => $category->id,
                'sku' => 'PGD-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'name' => "Paged Product {$number}",
                'price' => 1000 * $number,
                'stock' => 10 + $number,
                'unit' => 'pcs',
            ]);

            $sale = Sale::create([
                'invoice_number' => "INV-PAGED-{$number}",
                'customer_id' => $customer->id,
                'cashier_name' => 'Admin',
                'payment_method' => 'cash',
                'subtotal' => 1000 * $number,
                'discount' => 0,
                'total' => 1000 * $number,
                'sold_at' => now()->subMinutes($number),
                'notes' => null,
            ]);

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
                'line_total' => $product->price,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('sales.index'));

        $response->assertOk();
        $response->assertSee('UD PINDO Admin');
        $response->assertSee('Customer payment');
        $response->assertSee('Estimated change');
        $response->assertSee('Search product');
        $response->assertDontSee('Available products');
        $response->assertSee('Discount');
        $response->assertSee('Showing 1-5 of 6 transactions');
        $response->assertSee('Purchase details');
        $response->assertSee('Paged Product 1');
        $response->assertSee('1 x Rp 1,000.00');

        $secondPageResponse = $this->actingAs($this->user)->get(route('sales.index', [
            'recent_sales_page' => 2,
        ]));

        $secondPageResponse->assertOk();
        $secondPageResponse->assertSee('Showing 6-6 of 6 transactions');
    }

    public function test_recent_transactions_can_be_filtered_and_sorted_by_date(): void
    {
        $customer = Customer::create([
            'name' => 'Date Filter Customer',
            'email' => 'datefilter@example.com',
        ]);

        $olderSale = Sale::create([
            'invoice_number' => 'INV-DATE-OLD',
            'customer_id' => $customer->id,
            'cashier_name' => 'Admin',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'total' => 10000,
            'sold_at' => now()->subDays(2),
            'notes' => null,
        ]);

        $newerSale = Sale::create([
            'invoice_number' => 'INV-DATE-NEW',
            'customer_id' => $customer->id,
            'cashier_name' => 'Admin',
            'payment_method' => 'cash',
            'subtotal' => 12000,
            'discount' => 0,
            'total' => 12000,
            'sold_at' => now()->subDay(),
            'notes' => null,
        ]);

        $filteredResponse = $this->actingAs($this->user)->get(route('sales.index', [
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
            'sort' => 'latest',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('INV-DATE-NEW');
        $filteredResponse->assertDontSee('INV-DATE-OLD');

        $sortedResponse = $this->actingAs($this->user)->get(route('sales.index', [
            'sort' => 'oldest',
        ]));

        $sortedResponse->assertOk();
        $sortedResponse->assertSeeInOrder([
            $olderSale->invoice_number,
            $newerSale->invoice_number,
        ]);
    }

    public function test_recent_transactions_can_be_searched_by_product_name(): void
    {
        $customer = Customer::create([
            'name' => 'Search Customer',
            'email' => 'search@example.com',
        ]);

        $category = Category::create([
            'name' => 'Search Category',
            'description' => 'Searchable items',
        ]);

        $matchingProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'SRC-100',
            'name' => 'Chocolate Milk',
            'price' => 12000,
            'stock' => 10,
            'unit' => 'bottle',
        ]);

        $otherProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'SRC-200',
            'name' => 'Orange Juice',
            'price' => 14000,
            'stock' => 10,
            'unit' => 'bottle',
        ]);

        $matchingSale = Sale::create([
            'invoice_number' => 'INV-SRC-MILK',
            'customer_id' => $customer->id,
            'cashier_name' => 'Admin',
            'payment_method' => 'cash',
            'subtotal' => 12000,
            'discount' => 0,
            'total' => 12000,
            'sold_at' => now()->subHour(),
            'notes' => null,
        ]);

        $otherSale = Sale::create([
            'invoice_number' => 'INV-SRC-JUICE',
            'customer_id' => $customer->id,
            'cashier_name' => 'Admin',
            'payment_method' => 'cash',
            'subtotal' => 14000,
            'discount' => 0,
            'total' => 14000,
            'sold_at' => now(),
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $matchingSale->id,
            'product_id' => $matchingProduct->id,
            'quantity' => 1,
            'unit_price' => $matchingProduct->price,
            'line_total' => $matchingProduct->price,
        ]);

        SaleItem::create([
            'sale_id' => $otherSale->id,
            'product_id' => $otherProduct->id,
            'quantity' => 1,
            'unit_price' => $otherProduct->price,
            'line_total' => $otherProduct->price,
        ]);

        $response = $this->actingAs($this->user)->get(route('sales.index', [
            'search' => 'Chocolate',
        ]));

        $response->assertOk();
        $response->assertSee('INV-SRC-MILK');
        $response->assertDontSee('INV-SRC-JUICE');
    }

    public function test_recent_transactions_can_be_searched_by_invoice(): void
    {
        $customer = Customer::create([
            'name' => 'Invoice Search Customer',
            'email' => 'invoice-search@example.com',
        ]);

        $matchingSale = Sale::create([
            'invoice_number' => 'INV-SEARCH-001',
            'customer_id' => $customer->id,
            'cashier_name' => 'Admin',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'total' => 10000,
            'customer_payment' => 10000,
            'change_amount' => 0,
            'sold_at' => now()->subHour(),
            'notes' => null,
        ]);

        $otherSale = Sale::create([
            'invoice_number' => 'INV-OTHER-002',
            'customer_id' => $customer->id,
            'cashier_name' => 'Admin',
            'payment_method' => 'cash',
            'subtotal' => 12000,
            'discount' => 0,
            'total' => 12000,
            'customer_payment' => 12000,
            'change_amount' => 0,
            'sold_at' => now(),
            'notes' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('sales.index', [
            'search' => 'INV-SEARCH-001',
        ]));

        $response->assertOk();
        $response->assertSee($matchingSale->invoice_number);
        $response->assertDontSee($otherSale->invoice_number);
    }

    public function test_recent_transactions_can_be_exported_to_excel_based_on_date(): void
    {
        $customer = Customer::create([
            'name' => 'Export Customer',
            'email' => 'export@example.com',
        ]);

        $category = Category::create([
            'name' => 'Export Category',
            'description' => 'Export items',
        ]);

        $matchingProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'EXP-100',
            'name' => 'Export Match Product',
            'price' => 10000,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $otherProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'EXP-200',
            'name' => 'Export Other Product',
            'price' => 12000,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $matchingSale = Sale::create([
            'invoice_number' => 'INV-EXP-MATCH',
            'customer_id' => $customer->id,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'total' => 10000,
            'customer_payment' => 10000,
            'change_amount' => 0,
            'sold_at' => now()->subDay(),
            'notes' => null,
        ]);

        $otherSale = Sale::create([
            'invoice_number' => 'INV-EXP-OTHER',
            'customer_id' => $customer->id,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 12000,
            'discount' => 0,
            'total' => 12000,
            'customer_payment' => 12000,
            'change_amount' => 0,
            'sold_at' => now()->subDays(3),
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $matchingSale->id,
            'product_id' => $matchingProduct->id,
            'quantity' => 1,
            'unit_price' => $matchingProduct->price,
            'line_total' => $matchingProduct->price,
        ]);

        SaleItem::create([
            'sale_id' => $otherSale->id,
            'product_id' => $otherProduct->id,
            'quantity' => 1,
            'unit_price' => $otherProduct->price,
            'line_total' => $otherProduct->price,
        ]);

        $response = $this->actingAs($this->user)->get(route('sales.export-recent-transactions', [
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
            'search' => 'Match',
            'sort' => 'latest',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertSee('INV-EXP-MATCH');
        $response->assertDontSee('INV-EXP-OTHER');
        $response->assertSee('Export Match Product');
        $response->assertSee('Export Match Product (1 x Rp 10,000.00)');
    }

    public function test_recent_transactions_can_be_exported_to_excel_based_on_category(): void
    {
        $customer = Customer::create([
            'name' => 'Category Export Customer',
            'email' => 'categoryexport@example.com',
        ]);

        $matchingCategory = Category::create([
            'name' => 'Category Match',
            'description' => 'Matching export category',
        ]);

        $otherCategory = Category::create([
            'name' => 'Category Other',
            'description' => 'Other export category',
        ]);

        $matchingProduct = Product::create([
            'category_id' => $matchingCategory->id,
            'sku' => 'CAT-100',
            'name' => 'Category Match Product',
            'price' => 10000,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $otherProduct = Product::create([
            'category_id' => $otherCategory->id,
            'sku' => 'CAT-200',
            'name' => 'Category Other Product',
            'price' => 12000,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $matchingSale = Sale::create([
            'invoice_number' => 'INV-CAT-MATCH',
            'customer_id' => $customer->id,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'total' => 10000,
            'customer_payment' => 10000,
            'change_amount' => 0,
            'sold_at' => now(),
            'notes' => null,
        ]);

        $otherSale = Sale::create([
            'invoice_number' => 'INV-CAT-OTHER',
            'customer_id' => $customer->id,
            'cashier_name' => 'UD PINDO Admin',
            'payment_method' => 'cash',
            'subtotal' => 12000,
            'discount' => 0,
            'total' => 12000,
            'customer_payment' => 12000,
            'change_amount' => 0,
            'sold_at' => now(),
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $matchingSale->id,
            'product_id' => $matchingProduct->id,
            'quantity' => 1,
            'unit_price' => $matchingProduct->price,
            'line_total' => $matchingProduct->price,
        ]);

        SaleItem::create([
            'sale_id' => $otherSale->id,
            'product_id' => $otherProduct->id,
            'quantity' => 1,
            'unit_price' => $otherProduct->price,
            'line_total' => $otherProduct->price,
        ]);

        $response = $this->actingAs($this->user)->get(route('sales.export-recent-transactions', [
            'category_id' => $matchingCategory->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertSee('Category Match');
        $response->assertSee('INV-CAT-MATCH');
        $response->assertDontSee('INV-CAT-OTHER');
        $response->assertDontSee('Category Other');
    }

    public function test_admin_cannot_use_discount_and_does_not_see_discount_field(): void
    {
        $adminUser = User::factory()->create([
            'name' => 'Cashier Admin',
            'username' => 'cashieradmin',
            'role' => User::ROLE_ADMIN,
        ]);

        $category = Category::create([
            'name' => 'Admin Beverages',
            'description' => 'Admin sale category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ADM-100',
            'name' => 'Admin Drink',
            'price' => 15000,
            'stock' => 10,
            'unit' => 'bottle',
        ]);

        $indexResponse = $this->actingAs($adminUser)->get(route('sales.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('for="discount"', false);
        $indexResponse->assertSee('disabled', false);

        $storeResponse = $this->actingAs($adminUser)->post(route('sales.store'), [
            'payment_method' => 'cash',
            'customer_payment' => 20000,
            'discount' => 5000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $storeResponse->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'cashier_name' => 'Cashier Admin',
            'subtotal' => 15000,
            'discount' => 0,
            'total' => 15000,
            'customer_payment' => 20000,
            'change_amount' => 5000,
        ]);
    }

    public function test_sale_cannot_be_completed_when_customer_payment_is_less_than_total(): void
    {
        $category = Category::create([
            'name' => 'Payment Validation Category',
            'description' => 'Payment validation items',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'PAY-100',
            'name' => 'Payment Check Product',
            'price' => 15000,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)->from(route('sales.index'))->post(route('sales.store'), [
            'payment_method' => 'cash',
            'customer_payment' => 10000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHasErrors('customer_payment');
        $this->assertDatabaseMissing('sales', [
            'cashier_name' => 'UD PINDO Admin',
            'subtotal' => 15000,
            'customer_payment' => 10000,
        ]);
    }

    public function test_transaction_records_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get(route('transaction-records.index'));

        $response->assertOk();
        $response->assertSee('Transaction records');
        $response->assertSee('Record transaction');
    }

    public function test_transaction_record_can_be_created(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 15, 30, 0, 'Asia/Jakarta'));

        $response = $this->actingAs($this->user)->post(route('transaction-records.store'), [
            'type' => TransactionRecord::TYPE_EXPENSE,
            'category' => 'Office Supplies',
            'amount' => 25000,
            'payment_method' => 'cash',
            'notes' => 'Printer paper purchase',
        ]);

        $response->assertRedirect(route('transaction-records.index'));

        $this->assertDatabaseHas('transaction_records', [
            'type' => TransactionRecord::TYPE_EXPENSE,
            'category' => 'Office Supplies',
            'amount' => 25000,
            'payment_method' => 'cash',
            'user_id' => $this->user->id,
            'transaction_date' => '2026-04-06 15:30:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_transaction_records_can_be_filtered_and_sorted_by_date(): void
    {
        $olderRecord = TransactionRecord::create([
            'reference_number' => 'TRX-OLD-001',
            'user_id' => $this->user->id,
            'type' => TransactionRecord::TYPE_EXPENSE,
            'category' => 'Older Expense',
            'amount' => 10000,
            'payment_method' => 'cash',
            'transaction_date' => now()->subDays(2),
            'notes' => null,
        ]);

        $newerRecord = TransactionRecord::create([
            'reference_number' => 'TRX-NEW-001',
            'user_id' => $this->user->id,
            'type' => TransactionRecord::TYPE_INCOME,
            'category' => 'Newer Income',
            'amount' => 20000,
            'payment_method' => 'transfer',
            'transaction_date' => now()->subDay(),
            'notes' => null,
        ]);

        $filteredResponse = $this->actingAs($this->user)->get(route('transaction-records.index', [
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
            'sort' => 'latest',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('TRX-NEW-001');
        $filteredResponse->assertDontSee('TRX-OLD-001');

        $sortedResponse = $this->actingAs($this->user)->get(route('transaction-records.index', [
            'sort' => 'oldest',
        ]));

        $sortedResponse->assertOk();
        $sortedResponse->assertSeeInOrder([
            $olderRecord->reference_number,
            $newerRecord->reference_number,
        ]);
        $sortedResponse->assertSee('Debit');
        $sortedResponse->assertSee('Credit');
        $sortedResponse->assertSee('Gain');
        $sortedResponse->assertSee('Rp 20,000.00');
        $sortedResponse->assertSee('Rp 10,000.00');
        $sortedResponse->assertSee('Rp 10,000.00');
    }

    public function test_transaction_records_can_be_exported_to_excel(): void
    {
        $matchingRecord = TransactionRecord::create([
            'reference_number' => 'TRX-EXP-001',
            'user_id' => $this->user->id,
            'type' => TransactionRecord::TYPE_INCOME,
            'category' => 'Export Match',
            'amount' => 20000,
            'payment_method' => 'cash',
            'transaction_date' => now()->subDay(),
            'notes' => 'Matching export record',
        ]);

        $otherRecord = TransactionRecord::create([
            'reference_number' => 'TRX-EXP-002',
            'user_id' => $this->user->id,
            'type' => TransactionRecord::TYPE_EXPENSE,
            'category' => 'Export Other',
            'amount' => 10000,
            'payment_method' => 'transfer',
            'transaction_date' => now()->subDays(3),
            'notes' => 'Other export record',
        ]);

        $response = $this->actingAs($this->user)->get(route('transaction-records.export', [
            'search' => 'Match',
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
            'sort' => 'latest',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertSee('Transaction Records Export');
        $response->assertSee('Debit total: 20000.00 | Credit total: 0.00 | Gain: 20000.00');
        $response->assertSee($matchingRecord->reference_number);
        $response->assertDontSee($otherRecord->reference_number);
    }

    public function test_warehouse_page_only_lists_products_in_gudang_category(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $retailCategory = Category::create([
            'name' => 'Retail',
            'description' => 'Produk display',
        ]);

        Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-001',
            'name' => 'Gudang Beras',
            'price' => 70000,
            'stock' => 12,
            'unit' => 'sak',
        ]);

        Product::create([
            'category_id' => $retailCategory->id,
            'sku' => 'RTL-001',
            'name' => 'Rak Display',
            'price' => 15000,
            'stock' => 6,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)->get(route('warehouse.index'));

        $response->assertOk();
        $response->assertSee('Gudang Beras');
        $response->assertDontSee('Rak Display');
    }

    public function test_warehouse_can_add_stock_with_notes(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $product = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-ADD-001',
            'name' => 'Gula Gudang',
            'price' => 18000,
            'stock' => 10,
            'unit' => 'karung',
        ]);

        $response = $this->actingAs($this->user)->post(route('warehouse.store'), [
            'product_id' => $product->id,
            'type' => WarehouseStockMovement::TYPE_ADD,
            'quantity' => 5,
            'notes' => 'Barang masuk dari supplier utama',
        ]);

        $response->assertRedirect(route('warehouse.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 15,
        ]);

        $this->assertDatabaseHas('warehouse_stock_movements', [
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'type' => WarehouseStockMovement::TYPE_ADD,
            'quantity' => 5,
            'notes' => 'Barang masuk dari supplier utama',
        ]);
    }

    public function test_warehouse_cannot_remove_more_stock_than_available(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $product = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-REM-001',
            'name' => 'Minyak Gudang',
            'price' => 25000,
            'stock' => 3,
            'unit' => 'dus',
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('warehouse.index'))
            ->post(route('warehouse.store'), [
                'product_id' => $product->id,
                'type' => WarehouseStockMovement::TYPE_REMOVE,
                'quantity' => 5,
                'notes' => 'Stok rusak dipisahkan',
            ]);

        $response->assertRedirect(route('warehouse.index'));
        $response->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);

        $this->assertDatabaseMissing('warehouse_stock_movements', [
            'product_id' => $product->id,
            'type' => WarehouseStockMovement::TYPE_REMOVE,
            'quantity' => 5,
        ]);
    }

    public function test_warehouse_history_can_be_searched_and_sorted(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $olderProduct = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-HIS-001',
            'name' => 'Beras Premium',
            'price' => 80000,
            'stock' => 20,
            'unit' => 'sak',
        ]);

        $newerProduct = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-HIS-002',
            'name' => 'Gula Pasir',
            'price' => 18000,
            'stock' => 30,
            'unit' => 'karung',
        ]);

        $olderMovement = WarehouseStockMovement::create([
            'product_id' => $olderProduct->id,
            'user_id' => $this->user->id,
            'type' => WarehouseStockMovement::TYPE_ADD,
            'quantity' => 4,
            'notes' => 'Stok beras masuk',
            'moved_at' => now()->subDay(),
        ]);

        $newerMovement = WarehouseStockMovement::create([
            'product_id' => $newerProduct->id,
            'user_id' => $this->user->id,
            'type' => WarehouseStockMovement::TYPE_REMOVE,
            'quantity' => 2,
            'notes' => 'Gula dikirim ke cabang',
            'moved_at' => now(),
        ]);

        $searchResponse = $this->actingAs($this->user)->get(route('warehouse.index', [
            'search' => 'beras',
            'sort' => 'latest',
        ]));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Beras Premium');
        $searchResponse->assertSee('Stok beras masuk');
        $searchResponse->assertDontSee('Gula dikirim ke cabang');

        $sortedResponse = $this->actingAs($this->user)->get(route('warehouse.index', [
            'sort' => 'oldest',
        ]));

        $sortedResponse->assertOk();
        $sortedResponse->assertSeeInOrder([
            $olderMovement->product->name,
            $newerMovement->product->name,
        ]);

        $dateFilteredResponse = $this->actingAs($this->user)->get(route('warehouse.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'sort' => 'latest',
        ]));

        $dateFilteredResponse->assertOk();
        $dateFilteredResponse->assertSee('Gula dikirim ke cabang');
        $dateFilteredResponse->assertDontSee('Stok beras masuk');
    }

    public function test_restock_page_lists_products_from_all_categories(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $retailCategory = Category::create([
            'name' => 'Retail',
            'description' => 'Produk umum',
        ]);

        Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-RST-001',
            'name' => 'Barang Gudang',
            'price' => 10000,
            'stock' => 4,
            'unit' => 'pcs',
        ]);

        Product::create([
            'category_id' => $retailCategory->id,
            'sku' => 'RTL-RST-001',
            'name' => 'Barang Toko',
            'price' => 12000,
            'stock' => 7,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)->get(route('restock.index'));

        $response->assertOk();
        $response->assertSee('Barang Toko');
        $response->assertSee('Barang Gudang');
    }

    public function test_restock_can_add_stock_with_required_notes(): void
    {
        $retailCategory = Category::create([
            'name' => 'Retail',
            'description' => 'Produk umum',
        ]);

        $product = Product::create([
            'category_id' => $retailCategory->id,
            'sku' => 'RST-ADD-001',
            'name' => 'Sabun Restock',
            'price' => 15000,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)->post(route('restock.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'notes' => 'Tambahan stok dari supplier utama',
        ]);

        $response->assertRedirect(route('restock.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 15,
        ]);

        $this->assertDatabaseHas('warehouse_stock_movements', [
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'type' => WarehouseStockMovement::TYPE_ADD,
            'quantity' => 5,
            'notes' => 'Tambahan stok dari supplier utama',
        ]);
    }

    public function test_restock_requires_notes(): void
    {
        $gudangCategory = Category::create([
            'name' => 'Gudang',
            'description' => 'Produk gudang',
        ]);

        $product = Product::create([
            'category_id' => $gudangCategory->id,
            'sku' => 'GDG-RST-002',
            'name' => 'Barang Gudang Ditolak',
            'price' => 20000,
            'stock' => 3,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('restock.index'))
            ->post(route('restock.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
                'notes' => '',
            ]);

        $response->assertRedirect(route('restock.index'));
        $response->assertSessionHasErrors(['notes']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
    }

    public function test_user_can_log_in_with_database_credentials(): void
    {
        $response = $this->post(route('login.store'), [
            'username' => 'MYHS',
            'password' => 'udindo123',
        ]);

        $response->assertRedirect(route('sales.index'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_user_can_log_in_with_case_insensitive_username(): void
    {
        $response = $this->post(route('login.store'), [
            'username' => 'myhs',
            'password' => 'udindo123',
        ]);

        $response->assertRedirect(route('sales.index'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_authenticated_user_can_create_a_manageable_login_account(): void
    {
        $response = $this->actingAs($this->user)->post(route('users.store'), [
            'name' => 'Cashier Two',
            'username' => 'cashier2',
            'email' => 'cashier2@example.com',
            'password' => 'newpassword',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Cashier Two',
            'username' => 'cashier2',
            'email' => 'cashier2@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_authenticated_user_can_update_another_users_password(): void
    {
        $managedUser = User::factory()->create([
            'username' => 'cashier3',
            'email' => 'cashier3@example.com',
            'password' => 'oldpassword',
        ]);

        $response = $this->actingAs($this->user)->put(route('users.update', $managedUser), [
            'name' => $managedUser->name,
            'username' => $managedUser->username,
            'email' => $managedUser->email,
            'password' => 'updatedpass',
            'role' => User::ROLE_DEVELOPER,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue(Hash::check('updatedpass', $managedUser->fresh()->password));
        $this->assertSame(User::ROLE_DEVELOPER, $managedUser->fresh()->role);
    }

    public function test_admin_cannot_access_user_management(): void
    {
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($adminUser)->get(route('users.index'));

        $response->assertRedirect(route('sales.index'));

        $salesResponse = $this->actingAs($adminUser)->get(route('sales.index'));
        $salesResponse->assertDontSee('Users');
    }

    public function test_admin_cannot_access_warehouse(): void
    {
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($adminUser)->get(route('warehouse.index'));

        $response->assertRedirect(route('sales.index'));

        $salesResponse = $this->actingAs($adminUser)->get(route('sales.index'));
        $salesResponse->assertDontSee('Warehouse');
    }

    public function test_admin_can_access_restock_invoice_gudang_and_customers(): void
    {
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $restockResponse = $this->actingAs($adminUser)->get(route('restock.index'));
        $restockResponse->assertOk();

        $invoiceGudangResponse = $this->actingAs($adminUser)->get(route('warehouse-invoices.index'));
        $invoiceGudangResponse->assertOk();

        $customersResponse = $this->actingAs($adminUser)->get(route('customers.index'));
        $customersResponse->assertOk();

        $salesResponse = $this->actingAs($adminUser)->get(route('sales.index'));
        $salesResponse->assertSee('Restock');
        $salesResponse->assertSee('Invoice Gudang');
        $salesResponse->assertSee('Customers');
    }
}
