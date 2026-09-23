<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DryRunTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dry_run_create_shows_data_without_saving(): void
    {
        $this->from(route('clients.index'))
            ->post(route('dry-run.toggle'))
            ->assertRedirect()
            ->assertSessionHas('dry_run', true);

        $before = Client::query()->count();
        $name = 'Dry Run Co '.uniqid();

        $created = $this->post(route('clients.store'), [
            'form' => 'client-create',
            'comname' => $name,
            'comadd' => '12 Market Road',
            'comcity' => 'Manila',
            'comnob' => 'Wholesale',
            'bnkname' => 'BDO',
            'bnkbrn' => 'Makati',
        ]);

        $created->assertRedirect(route('clients.index'));
        $created->assertSessionHas('dry_run_report');

        $report = session('dry_run_report');
        $this->assertSame('Create client', $report['title']);
        $this->assertSame($name, $report['sections'][0]['items'][0]['after']['comname']);
        $this->assertMatchesRegularExpression('/^COM-\d{9}$/', $report['sections'][0]['items'][0]['after']['comcode']);
        $this->assertSame($before, Client::query()->count());
        $this->assertNull(Client::query()->where('comname', $name)->first());
    }

    public function test_dry_run_merge_combine_shows_effect_without_saving(): void
    {
        $this->from(route('clients.index'))
            ->post(route('dry-run.toggle'))
            ->assertSessionHas('dry_run', true);

        $token = 'DR'.substr(uniqid(), -8);
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

        $merged = $this->post(route('clients.merge', $source), [
            'form' => 'merge-'.$source->recid,
            'target' => $target->recid,
            'products' => [$sourceProduct->recid],
            'combine_products' => [$sourceProduct->recid],
        ]);

        $merged->assertRedirect(route('clients.index'));
        $merged->assertSessionHas('dry_run_report');
        $merged->assertSessionMissing('merge_delete');

        $report = session('dry_run_report');
        $this->assertStringContainsString('Merge', $report['title']);
        $this->assertSame('9', $report['sections'][0]['items'][0]['after']['License']);
        $this->assertNull($report['sections'][0]['items'][0]['after']['Version']);

        $this->assertModelExists($sourceProduct);
        $this->assertSame($source->comcode, $sourceProduct->refresh()->comcode);
        $this->assertSame('8', $sourceProduct->prdnoli);
        $targetProduct->refresh();
        $this->assertSame('1', $targetProduct->prdnoli);
        $this->assertSame('Additional', $targetProduct->prdvers);
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
