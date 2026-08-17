<?php

namespace Tests\Feature;

use App\Http\Livewire\Accounts;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\User;
use Database\Seeders\AccountTypeSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccountTypeSeeder::class);
    }

    public function test_guest_is_redirected_away_from_the_accounts_page(): void
    {
        $this->get('/contas')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_the_accounts_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/contas')->assertOk();
    }

    public function test_user_can_create_an_account(): void
    {
        $user = User::factory()->create();
        $type = AccountType::query()->where('key', 'checking')->firstOrFail();

        Livewire::actingAs($user)->test(Accounts::class)
            ->set('name', 'Nubank')
            ->set('institution', 'Nu Pagamentos S.A.')
            ->set('accountTypeId', $type->id)
            ->call('save');

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Nubank',
            'account_type_id' => $type->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_edit_their_own_account(): void
    {
        $user = User::factory()->create();
        $type = AccountType::query()->where('key', 'savings')->firstOrFail();
        $account = $this->actingAs($user)->createAccount($type->id, 'Conta antiga');

        Livewire::actingAs($user)->test(Accounts::class)
            ->call('edit', $account->id)
            ->set('name', 'Conta renomeada')
            ->set('status', 'archived')
            ->call('save');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Conta renomeada',
            'status' => 'archived',
        ]);
    }

    public function test_user_can_delete_their_own_account(): void
    {
        $user = User::factory()->create();
        $type = AccountType::query()->where('key', 'wallet')->firstOrFail();
        $account = $this->actingAs($user)->createAccount($type->id, 'Carteira');

        Livewire::actingAs($user)->test(Accounts::class)
            ->call('delete', $account->id);

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_user_cannot_edit_another_users_account(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $type = AccountType::query()->where('key', 'checking')->firstOrFail();
        $account = $this->actingAs($owner)->createAccount($type->id, 'Conta do dono');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($intruder)->test(Accounts::class)
            ->call('edit', $account->id);
    }

    public function test_user_cannot_delete_another_users_account(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $type = AccountType::query()->where('key', 'checking')->firstOrFail();
        $account = $this->actingAs($owner)->createAccount($type->id, 'Conta do dono');

        try {
            Livewire::actingAs($intruder)->test(Accounts::class)
                ->call('delete', $account->id);

            $this->fail('Expected a ModelNotFoundException to be thrown.');
        } catch (ModelNotFoundException) {
            // expected: the account is not visible under the intruder's scope
        }

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'deleted_at' => null]);
    }

    public function test_name_and_account_type_are_required(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Accounts::class)
            ->set('name', '')
            ->set('accountTypeId', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'accountTypeId' => 'required']);
    }

    private function createAccount(string $accountTypeId, string $name): Account
    {
        return Account::create([
            'account_type_id' => $accountTypeId,
            'name' => $name,
        ]);
    }
}
