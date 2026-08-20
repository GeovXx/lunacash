<?php

namespace Tests\Feature;

use App\Livewire\Categories;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    public function test_guest_is_redirected_away_from_the_categories_page(): void
    {
        $this->get('/categorias')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_the_categories_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/categorias')->assertOk();
    }

    public function test_default_categories_are_visible_but_read_only(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/categorias')
            ->assertOk()
            ->assertSee('Alimentação')
            ->assertSee('Somente leitura');
    }

    public function test_user_can_create_a_category(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Categories::class)
            ->set('name', 'Assinaturas')
            ->set('type', 'expense')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Assinaturas',
            'type' => 'expense',
        ]);
    }

    public function test_user_can_create_a_subcategory(): void
    {
        $user = User::factory()->create();
        $parent = $this->actingAs($user)->createCategory('Alimentação pessoal', 'expense');

        Livewire::actingAs($user)->test(Categories::class)
            ->set('name', 'Restaurantes')
            ->set('type', 'expense')
            ->set('parentId', $parent->id)
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Restaurantes',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_user_can_edit_their_own_category(): void
    {
        $user = User::factory()->create();
        $category = $this->actingAs($user)->createCategory('Antiga', 'expense');

        Livewire::actingAs($user)->test(Categories::class)
            ->call('edit', $category->id)
            ->set('name', 'Renomeada')
            ->call('save');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Renomeada']);
    }

    public function test_user_can_delete_their_own_category(): void
    {
        $user = User::factory()->create();
        $category = $this->actingAs($user)->createCategory('Descartável', 'expense');

        Livewire::actingAs($user)->test(Categories::class)
            ->call('delete', $category->id);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_edit_a_default_category(): void
    {
        $user = User::factory()->create();
        $default = Category::query()->whereNull('user_id')->firstOrFail();

        Livewire::actingAs($user)->test(Categories::class)
            ->call('edit', $default->id)
            ->assertStatus(403);
    }

    public function test_user_cannot_edit_another_users_category(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = $this->actingAs($owner)->createCategory('Privada', 'expense');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($intruder)->test(Categories::class)
            ->call('edit', $category->id);
    }

    public function test_name_and_type_are_required(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Categories::class)
            ->set('name', '')
            ->set('type', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'type' => 'required']);
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $user = User::factory()->create();
        $category = $this->actingAs($user)->createCategory('Principal', 'expense');

        Livewire::actingAs($user)->test(Categories::class)
            ->call('edit', $category->id)
            ->set('parentId', $category->id)
            ->call('save')
            ->assertHasErrors(['parentId']);
    }

    public function test_user_cannot_delete_a_default_category(): void
    {
        $user = User::factory()->create();
        $default = Category::query()->whereNull('user_id')->firstOrFail();

        Livewire::actingAs($user)->test(Categories::class)
            ->call('delete', $default->id)
            ->assertStatus(403);
    }

    public function test_user_can_create_a_transfer_category(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Categories::class)
            ->set('name', 'Transferência entre contas')
            ->set('type', 'transfer')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Transferência entre contas',
            'type' => 'transfer',
        ]);
    }

    private function createCategory(string $name, string $type): Category
    {
        return Category::create(['name' => $name, 'type' => $type]);
    }
}
