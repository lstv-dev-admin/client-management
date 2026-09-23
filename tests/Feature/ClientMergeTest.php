<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClientMergeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_merge_moves_selected_records_then_source_can_be_kept_or_deleted(): void
    {
        $token = 'ZM'.substr(uniqid(), -8);
        $source = $this->client($token.'A', 'Company A '.$token);
        $target = $this->client($token.'B', 'Company B '.$token);

        $stay = ClientProduct::query()->create([
            'comcode' => $source->comcode,
            'prdname' => 'Stay '.$token,
        ]);
        $moveProduct = ClientProduct::query()->create([
            'comcode' => $source->comcode,
            'prdname' => 'Move '.$token,
        ]);
        $moveContact = ClientContact::query()->create([
            'comcode' => $source->comcode,
            'conperson' => 'Marie '.$token,
            'condesig' => 'Buyer',
            'contactnum' => '09170000000',
            'conemail' => 'marie.'.$token.'@example.com',
        ]);

        $this->get(route('clients.index', ['q' => $token]))
            ->assertOk()
            ->assertSee('Merge');

        $search = $this->getJson(route('clients.search', [
            'q' => $token,
            'except' => $source->recid,
        ]));

        $search->assertOk();
        $ids = collect($search->json())->pluck('recid');
        $this->assertTrue($ids->contains($target->recid));
        $this->assertFalse($ids->contains($source->recid));

        $this->getJson(route('clients.summary', $target))
            ->assertOk()
            ->assertJsonPath('comcode', $target->comcode);

        $this->post(route('clients.merge', $source), [
            'form' => 'merge-'.$source->recid,
            'target' => $target->recid,
        ])->assertSessionHasErrors(['products'], null, 'merge-'.$source->recid);

        $merged = $this->post(route('clients.merge', $source), [
            'form' => 'merge-'.$source->recid,
            'target' => $target->recid,
            'products' => [$moveProduct->recid],
            'contacts' => [$moveContact->recid],
        ]);

        $merged->assertRedirect();
        $merged->assertSessionHas('merge_delete', $source->recid);

        $this->assertSame($target->comcode, $moveProduct->refresh()->comcode);
        $this->assertSame($target->comcode, $moveContact->refresh()->comcode);
        $this->assertSame($source->comcode, $stay->refresh()->comcode);
        $this->assertModelExists($source);

        $page = strtok($merged->headers->get('Location'), '#');
        $this->get($page)->assertSee('Delete this company? Products and contacts you did not move will also be removed.', false);

        $this->delete(route('clients.destroy', $source))->assertRedirect();

        $this->assertModelMissing($source);
        $this->assertModelMissing($stay);
        $this->assertModelExists($moveProduct);
        $this->assertModelExists($moveContact);
    }

    public function test_merge_combines_matching_product_licenses(): void
    {
        $token = 'CB'.substr(uniqid(), -8);
        $source = $this->client($token.'A', 'ABC Company '.$token);
        $target = $this->client($token.'B', 'ABC Company '.$token);

        $sourceProduct = ClientProduct::query()->create([
            'comcode' => $source->comcode,
            'prdname' => 'CS TKM EXP',
            'prdvers' => 'New',
            'prdnoli' => '8',
        ]);
        $targetProduct = ClientProduct::query()->create([
            'comcode' => $target->comcode,
            'prdname' => 'cs tkm exp',
            'prdvers' => 'Additional',
            'prdnoli' => '1',
        ]);
        $unique = ClientProduct::query()->create([
            'comcode' => $source->comcode,
            'prdname' => 'Other '.$token,
            'prdvers' => '1.0',
            'prdnoli' => '2',
        ]);

        $this->post(route('clients.merge', $source), [
            'form' => 'merge-'.$source->recid,
            'target' => $target->recid,
            'products' => [$sourceProduct->recid, $unique->recid],
            'combine_products' => [$sourceProduct->recid],
        ])->assertRedirect();

        $this->assertModelMissing($sourceProduct);
        $targetProduct->refresh();
        $this->assertSame($target->comcode, $targetProduct->comcode);
        $this->assertSame('9', $targetProduct->prdnoli);
        $this->assertNull($targetProduct->prdvers);
        $this->assertSame($target->comcode, $unique->refresh()->comcode);
        $this->assertSame(
            1,
            ClientProduct::query()
                ->where('comcode', $target->comcode)
                ->whereRaw('lower(trim(prdname)) = ?', ['cs tkm exp'])
                ->count()
        );
    }

    public function test_merge_skips_matching_products_that_are_not_combined(): void
    {
        $token = 'SK'.substr(uniqid(), -8);
        $source = $this->client($token.'A', 'ABC Company '.$token);
        $target = $this->client($token.'B', 'ABC Company '.$token);

        $sourceProduct = ClientProduct::query()->create([
            'comcode' => $source->comcode,
            'prdname' => 'CS TKM EXP',
            'prdvers' => 'New',
            'prdnoli' => '8',
        ]);
        $targetProduct = ClientProduct::query()->create([
            'comcode' => $target->comcode,
            'prdname' => 'CS TKM EXP',
            'prdvers' => 'Additional',
            'prdnoli' => '1',
        ]);

        $this->post(route('clients.merge', $source), [
            'form' => 'merge-'.$source->recid,
            'target' => $target->recid,
            'products' => [$sourceProduct->recid],
            'combine_products' => [],
        ])->assertRedirect();

        $this->assertSame($source->comcode, $sourceProduct->refresh()->comcode);
        $targetProduct->refresh();
        $this->assertSame('1', $targetProduct->prdnoli);
        $this->assertSame('Additional', $targetProduct->prdvers);
        $this->assertSame(
            1,
            ClientProduct::query()
                ->where('comcode', $target->comcode)
                ->where('prdname', 'CS TKM EXP')
                ->count()
        );
    }

    private function client(string $code, string $name): Client
    {
        return Client::query()->create([
            'comcode' => $code,
            'comname' => $name,
            'comadd' => '12 Market Road',
            'comcity' => 'Manila',
            'comnob' => 'Wholesale',
            'bnkname' => 'BDO',
            'bnkbrn' => 'Makati',
        ]);
    }
}
