<?php

namespace Tests\Feature;

use App\Services\ProductStore;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().'/products-test-'.uniqid().'.json';
        $this->app->instance(ProductStore::class, new ProductStore($this->path));
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_page_renders_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Product name')
            ->assertSee('Quantity in stock')
            ->assertSee('Price per item')
            ->assertSee('bootstrap', false);
    }

    public function test_submission_is_saved_as_valid_json(): void
    {
        $this->postJson('/products', ['product_name' => 'Pen', 'quantity' => '3', 'price' => '1.50'])
            ->assertCreated()
            ->assertJsonPath('product.total_value', 4.5)
            ->assertJsonPath('grand_total', 4.5);

        $data = json_decode(file_get_contents($this->path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Pen', $data['products'][0]['product_name']);
        $this->assertSame(3, $data['products'][0]['quantity']);
    }

    public function test_list_is_ordered_by_submitted_datetime_with_totals(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');
        $this->postJson('/products', ['product_name' => 'First', 'quantity' => 2, 'price' => 10]);
        Carbon::setTestNow('2026-01-01 11:00:00');
        $this->postJson('/products', ['product_name' => 'Second', 'quantity' => 3, 'price' => 2.5]);

        $this->getJson('/products')
            ->assertOk()
            ->assertJsonPath('products.0.product_name', 'First')
            ->assertJsonPath('products.0.total_value', 20)
            ->assertJsonPath('products.1.product_name', 'Second')
            ->assertJsonPath('products.1.total_value', 7.5)
            ->assertJsonPath('grand_total', 27.5);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->postJson('/products', ['product_name' => '', 'quantity' => 'abc', 'price' => '-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_name', 'quantity', 'price']);

        $this->assertFileDoesNotExist($this->path);
    }

    public function test_product_can_be_edited_keeping_submitted_datetime(): void
    {
        $id = $this->postJson('/products', ['product_name' => 'Pen', 'quantity' => 3, 'price' => 1.5])
            ->json('product.id');
        $submittedAt = $this->getJson('/products')->json('products.0.submitted_at');

        $this->putJson("/products/{$id}", ['product_name' => 'Blue pen', 'quantity' => 10, 'price' => 2])
            ->assertOk()
            ->assertJsonPath('product.product_name', 'Blue pen')
            ->assertJsonPath('product.submitted_at', $submittedAt)
            ->assertJsonPath('grand_total', 20);
    }

    public function test_editing_unknown_product_returns_404(): void
    {
        $this->putJson('/products/'.fake()->uuid(), ['product_name' => 'X', 'quantity' => 1, 'price' => 1])
            ->assertNotFound();
    }
}
