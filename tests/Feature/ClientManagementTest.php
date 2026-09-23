<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_client_product_and_contact_crud(): void
    {
        $this->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Client Management')
            ->assertSee('New client');

        $invalid = $this->from(route('clients.index'))
            ->post(route('clients.store'), [
                'form' => 'client-create',
                'comcode' => '',
                'comname' => '',
                'comadd' => '',
                'comcity' => '',
                'comnob' => '',
                'bnkname' => '',
                'bnkbrn' => '',
            ]);

        $invalid->assertRedirect(route('clients.index').'#new-client')
            ->assertSessionHasErrors([
                'comname',
                'comadd',
                'comcity',
                'comnob',
                'bnkname',
                'bnkbrn',
            ], null, 'client-create')
            ->assertSessionDoesntHaveErrors(['comcode'], null, 'client-create');

        $this->get(route('clients.index'))
            ->assertDontSee('The Company Code field is required.')
            ->assertSee('editing: true', false);

        $name = 'Acme Supplies '.substr(uniqid(), -8);

        $created = $this->post(route('clients.store'), $this->clientPayload('POSTED-CODE', $name));
        $created->assertRedirect();

        $client = Client::query()->where('comname', $name)->firstOrFail();
        $this->assertNotSame('POSTED-CODE', $client->comcode);
        $this->assertMatchesRegularExpression('/^COM-\d{9,}$/', $client->comcode);
        $created->assertRedirectContains('#client-'.$client->recid);

        $this->get(strtok($created->headers->get('Location'), '#'))
            ->assertOk()
            ->assertSee($name)
            ->assertSee('Company Code')
            ->assertSee('Complete Address')
            ->assertSee('Nature of Business')
            ->assertSee('Bank Code')
            ->assertSee('Bank Branch')
            ->assertSee('Products')
            ->assertSee('Company Contacts')
            ->assertSee('No products yet')
            ->assertSee('No contacts yet');

        $this->get(route('clients.index', ['q' => $client->comcode]))
            ->assertOk()
            ->assertSee($name)
            ->assertSee('1 client');

        $renamed = 'ZZ'.substr(uniqid(), -8);

        $this->put(route('clients.update', $client), $this->clientPayload($renamed, 'Acme Trading'))
            ->assertRedirectContains('#client-'.$client->recid);

        $client->refresh();
        $this->assertSame($renamed, $client->comcode);
        $this->assertSame('Acme Trading', $client->comname);

        $this->post(route('clients.products.store', $client), [
            'form' => 'product-new-'.$client->recid,
            'prdname' => 'Cement',
        ])->assertRedirectContains('#client-'.$client->recid);

        $product = ClientProduct::query()->where('comcode', $renamed)->where('prdname', 'Cement')->firstOrFail();

        $this->put(route('clients.products.update', [$client, $product]), [
            'form' => 'product-'.$product->recid,
            'prdname' => 'Cement Plus',
        ])->assertRedirectContains('#client-'.$client->recid);

        $this->assertSame('Cement Plus', $product->refresh()->prdname);

        $this->from(route('clients.index'))
            ->post(route('clients.contacts.store', $client), [
                'form' => 'contact-new-'.$client->recid,
                'conperson' => 'Ada',
                'condesig' => 'Buyer',
                'contactnum' => '09170000000',
                'conemail' => 'not-an-email',
            ])
            ->assertRedirectContains('#client-'.$client->recid)
            ->assertSessionHasErrors(['conemail'], null, 'contact-new-'.$client->recid);

        $this->post(route('clients.contacts.store', $client), [
            'form' => 'contact-new-'.$client->recid,
            'conperson' => 'Ada Lopez',
            'condesig' => 'Buyer',
            'contactnum' => '09170000000',
            'conemail' => 'ada@example.com',
        ])->assertRedirectContains('#client-'.$client->recid);

        $contact = ClientContact::query()->where('comcode', $renamed)->where('conemail', 'ada@example.com')->firstOrFail();

        $this->put(route('clients.contacts.update', [$client, $contact]), [
            'form' => 'contact-'.$contact->recid,
            'conperson' => 'Ada Cruz',
            'condesig' => 'Manager',
            'contactnum' => '09171111111',
            'conemail' => 'ada.cruz@example.com',
        ])->assertRedirectContains('#client-'.$client->recid);

        $contact->refresh();
        $this->assertSame('Ada Cruz', $contact->conperson);
        $this->assertSame('Manager', $contact->condesig);

        $renamedAgain = 'ZY'.substr(uniqid(), -8);

        $this->put(route('clients.update', $client), $this->clientPayload($renamedAgain, 'Acme Trading'))
            ->assertRedirect();

        $this->assertSame($renamedAgain, $product->refresh()->comcode);
        $this->assertSame($renamedAgain, $contact->refresh()->comcode);

        $this->delete(route('clients.products.destroy', [$client, $product]))
            ->assertRedirectContains('#client-'.$client->recid);
        $this->assertModelMissing($product);

        $this->delete(route('clients.contacts.destroy', [$client, $contact]))
            ->assertRedirectContains('#client-'.$client->recid);
        $this->assertModelMissing($contact);

        $replacement = ClientProduct::query()->create([
            'comcode' => $client->refresh()->comcode,
            'prdname' => 'Spare part',
        ]);

        $this->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertModelMissing($client);
        $this->assertModelMissing($replacement);
    }

    public function test_new_client_code_follows_the_highest_com_number(): void
    {
        $this->assertSame('COM-000000740', Client::comcodeFromNumber(740));
        $this->assertSame('COM-000000741', Client::comcodeFromNumber(741));

        if (! Client::query()->where('comcode', 'COM-000000740')->exists()) {
            Client::query()->create([
                'comcode' => 'COM-000000740',
                'comname' => 'Anchor 740 '.uniqid(),
                'comadd' => '12 Market Road',
                'comcity' => 'Manila',
                'comnob' => 'Wholesale',
                'bnkname' => 'BDO',
                'bnkbrn' => 'Makati',
            ]);
        }

        $highest = (int) Client::query()
            ->whereRaw("comcode REGEXP '^COM-[0-9]+$'")
            ->max(DB::raw('CAST(SUBSTRING(comcode, 5) AS UNSIGNED)'));

        $name = 'Generated Code '.uniqid();

        $created = $this->post(route('clients.store'), $this->clientPayload('COM-000000740', $name));
        $created->assertRedirect();

        $client = Client::query()->where('comname', $name)->firstOrFail();
        $this->assertSame(Client::comcodeFromNumber($highest + 1), $client->comcode);

        if ($highest === 740) {
            $this->assertSame('COM-000000741', $client->comcode);
        }
    }

    /**
     * @return array<string, string>
     */
    private function clientPayload(string $code, string $name): array
    {
        return [
            'form' => 'client-create',
            'comcode' => $code,
            'comname' => $name,
            'comadd' => '12 Market Road',
            'comcity' => 'Manila',
            'comnob' => 'Wholesale',
            'bnkname' => 'BDO',
            'bnkbrn' => 'Makati',
        ];
    }
}
