<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClientSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cards_collapse_and_rbk_cards_use_an_amber_background(): void
    {
        $token = 'RBK'.substr(uniqid(), -8);
        $rbk = $this->client('R'.$token, 'Amber '.$token, ' rbk ');
        $other = $this->client('O'.$token, 'Plain '.$token, 'BDO');

        $page = $this->get(route('clients.index', ['q' => $token]));

        $page->assertOk()
            ->assertSee('Show or hide company details', false)
            ->assertSee('x-show="open"', false)
            ->assertSee('open: false', false)
            ->assertSee('x-on:input.debounce.300ms', false)
            ->assertDontSee('>Search</button>', false)
            ->assertSee('aria-label="Show or hide company details"', false);

        $html = $page->getContent();
        $this->assertMatchesRegularExpression(
            '/id="client-'.$rbk->recid.'"[^>]*border-amber-100 bg-\[#fffdf8\]/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/id="client-'.$other->recid.'"[^>]*border-slate-200 bg-white/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="client-'.$other->recid.'"[^>]*bg-\[#fffdf8\]/',
            $html
        );
    }

    public function test_list_orders_by_trimmed_company_name_and_keeps_blanks_last(): void
    {
        $token = 'ORD'.substr(uniqid(), -8);
        $blank = $this->client('B'.$token, '   ', 'BDO');
        $zebra = $this->client('Z'.$token, '  Zebra '.$token, 'BDO');
        $alpha = $this->client('A'.$token, 'Alpha '.$token, 'BDO');

        $this->get(route('clients.index', ['q' => $token]))
            ->assertOk()
            ->assertSeeInOrder([
                'Alpha '.$token,
                'Zebra '.$token,
                'B'.$token,
            ]);

        $search = $this->getJson(route('clients.search', ['q' => $token]));
        $search->assertOk();
        $this->assertSame(
            [$alpha->recid, $zebra->recid, $blank->recid],
            collect($search->json())->pluck('recid')->all()
        );
    }

    public function test_return_page_uses_the_trimmed_name_order(): void
    {
        $token = 'PG'.substr(uniqid(), -8);
        $last = null;

        for ($number = 1; $number <= 11; $number++) {
            $last = $this->client(
                $token.$number,
                sprintf('%s-%02d', $token, $number),
                'BDO'
            );
        }

        $this->put(route('clients.update', $last), [
            'form' => 'client-'.$last->recid,
            'q' => $token,
            'comcode' => $last->comcode,
            'comname' => $last->comname,
            'comadd' => $last->comadd,
            'comcity' => $last->comcity,
            'comnob' => $last->comnob,
            'bnkname' => $last->bnkname,
            'bnkbrn' => $last->bnkbrn,
        ])->assertRedirect(route('clients.index', [
            'q' => $token,
            'page' => 2,
        ]).'#client-'.$last->recid);
    }

    public function test_every_search_word_matches_company_product_or_contact_fields(): void
    {
        $token = 'SRC'.substr(uniqid(), -8);
        $company = $this->client('C'.$token, 'ANGELICA DL MOJICA '.$token, 'BDO');
        $company->update(['comadd' => '12 Riverside '.$token]);

        $byProduct = $this->client('P'.$token, 'Quiet Holdings '.$token, 'BDO');
        ClientProduct::query()->create([
            'comcode' => $byProduct->comcode,
            'prdname' => 'SKU'.$token,
        ]);

        $byContact = $this->client('K'.$token, 'Hidden Desk '.$token, 'BDO');
        ClientContact::query()->create([
            'comcode' => $byContact->comcode,
            'conperson' => 'Pat '.$token,
            'condesig' => 'Buyer',
            'contactnum' => '09170000000',
            'conemail' => 'pat.'.$token.'@example.com',
        ]);

        $page = $this->get(route('clients.index', ['q' => 'mojica angelica']));
        $page->assertOk()
            ->assertSee('ANGELICA DL MOJICA '.$token)
            ->assertSee('<mark class="rounded bg-yellow-200 px-0.5">ANGELICA</mark>', false)
            ->assertSee('<mark class="rounded bg-yellow-200 px-0.5">MOJICA</mark>', false)
            ->assertDontSee('Quiet Holdings '.$token)
            ->assertDontSee('Hidden Desk '.$token);

        $this->get(route('clients.index', ['q' => 'mojica missing'.$token]))
            ->assertOk()
            ->assertSee('No clients match your search.')
            ->assertDontSee('ANGELICA DL MOJICA '.$token);

        $productPage = $this->get(route('clients.index', ['q' => 'SKU'.$token]));
        $productPage->assertOk()
            ->assertSee('Quiet Holdings '.$token)
            ->assertSee('<mark class="rounded bg-yellow-200 px-0.5">SKU'.$token.'</mark>', false)
            ->assertDontSee('ANGELICA DL MOJICA '.$token);

        $this->get(route('clients.index', ['q' => 'pat.'.$token.'@example.com']))
            ->assertOk()
            ->assertSee('Hidden Desk '.$token)
            ->assertSee('<mark class="rounded bg-yellow-200 px-0.5">pat.'.$token.'@example.com</mark>', false);

        $this->get(route('clients.index', ['q' => 'Riverside '.$token]))
            ->assertOk()
            ->assertSee('ANGELICA DL MOJICA '.$token)
            ->assertDontSee('Quiet Holdings '.$token);

        $escaped = $this->client('E'.$token, 'Alpha <script> '.$token, 'BDO');
        $this->get(route('clients.index', ['q' => $escaped->comcode]))
            ->assertOk()
            ->assertSee('Alpha &lt;script&gt; '.$token, false)
            ->assertDontSee('<script>', false);

        $merge = $this->getJson(route('clients.search', [
            'q' => 'SKU'.$token,
            'except' => $company->recid,
        ]));
        $merge->assertOk()
            ->assertJsonPath('0.recid', $byProduct->recid)
            ->assertJsonPath('0.hint', 'Product: SKU'.$token);

        $contactSearch = $this->getJson(route('clients.search', ['q' => 'pat.'.$token]));
        $contactSearch->assertOk();
        $match = collect($contactSearch->json())->firstWhere('recid', $byContact->recid);
        $this->assertNotNull($match);
        $this->assertSame('Email: pat.'.$token.'@example.com', $match['hint']);
    }

    public function test_user_can_choose_how_many_clients_appear_on_a_page(): void
    {
        $default = $this->get(route('clients.index'));
        $default->assertOk()->assertSee('name="per"', false);
        $this->assertSame(10, substr_count($default->getContent(), 'id="client-'));

        $wider = $this->get(route('clients.index', ['per' => 25]));
        $wider->assertOk()->assertSee('value="25" selected', false);
        $this->assertSame(25, substr_count($wider->getContent(), 'id="client-'));

        $rejected = $this->get(route('clients.index', ['per' => 5000]));
        $rejected->assertOk()->assertSee('value="10" selected', false);
        $this->assertSame(10, substr_count($rejected->getContent(), 'id="client-'));

        $token = 'PP'.substr(uniqid(), -8);
        $last = null;

        for ($number = 1; $number <= 11; $number++) {
            $last = $this->client(
                $token.$number,
                sprintf('%s-%02d', $token, $number),
                'BDO'
            );
        }

        $this->put(route('clients.update', $last), [
            'form' => 'client-'.$last->recid,
            'q' => $token,
            'per' => 25,
            'comcode' => $last->comcode,
            'comname' => $last->comname,
            'comadd' => $last->comadd,
            'comcity' => $last->comcity,
            'comnob' => $last->comnob,
            'bnkname' => $last->bnkname,
            'bnkbrn' => $last->bnkbrn,
        ])->assertRedirect(route('clients.index', [
            'q' => $token,
            'per' => 25,
        ]).'#client-'.$last->recid);
    }

    public function test_bank_code_dropdown_lists_each_distinct_code_once(): void
    {
        $token = 'BNK'.substr(uniqid(), -8);
        $this->client('A'.$token, 'Alpha '.$token, $token);
        $this->client('B'.$token, 'Beta '.$token, ' '.strtolower($token).' ');

        $page = $this->get(route('clients.index', ['q' => $token]));
        $page->assertOk()->assertSee('All banks', false)->assertSee('name="bank"', false);

        $this->assertSame(1, preg_match('/<select[^>]*name="bank"[^>]*>(.*?)<\/select>/s', $page->getContent(), $select));
        $this->assertSame(1, substr_count($select[1], 'value="'.$token.'"'));
        $this->assertSame(0, substr_count($select[1], 'value="'.strtolower($token).'"'));
    }

    public function test_bank_filter_combines_with_search_and_ignores_unknown_codes(): void
    {
        $token = 'BFL'.substr(uniqid(), -8);
        $bank = 'ZB'.$token;
        $this->client('A'.$token, 'Alpha '.$token, $bank);
        $this->client('R'.$token, 'Zebra '.$token, 'RBK');

        $this->get(route('clients.index', ['bank' => strtolower($bank)]))
            ->assertOk()
            ->assertSee('Alpha '.$token)
            ->assertDontSee('Zebra '.$token)
            ->assertSee('value="'.$bank.'" selected', false);

        $this->get(route('clients.index', ['q' => $token, 'bank' => $bank]))
            ->assertOk()
            ->assertSee('Alpha '.$token)
            ->assertDontSee('Zebra '.$token);

        $this->get(route('clients.index', ['q' => 'Zebra '.$token, 'bank' => $bank]))
            ->assertOk()
            ->assertSee('No clients match your search.')
            ->assertSee('0 clients')
            ->assertDontSee('Alpha '.$token);

        $unknown = $this->get(route('clients.index', ['q' => $token, 'bank' => 'NO-SUCH-'.$token]));
        $unknown->assertOk()
            ->assertSee('Alpha '.$token)
            ->assertSee('Zebra '.$token);

        $this->assertSame(1, preg_match('/<select[^>]*name="bank"[^>]*>(.*?)<\/select>/s', $unknown->getContent(), $select));
        $this->assertStringNotContainsString('NO-SUCH-'.$token, $select[1]);
        $this->assertStringNotContainsString('selected', $select[1]);
    }

    public function test_saving_a_client_keeps_the_bank_filter_and_drops_it_when_the_bank_changes(): void
    {
        $token = 'BKP'.substr(uniqid(), -8);
        $bank = 'BK'.$token;
        $last = null;

        for ($number = 1; $number <= 11; $number++) {
            $last = $this->client(
                $token.$number,
                sprintf('%s-%02d', $token, $number),
                $bank
            );
        }

        $this->put(route('clients.update', $last), [
            'form' => 'client-'.$last->recid,
            'q' => $token,
            'bank' => $bank,
            'comcode' => $last->comcode,
            'comname' => $last->comname,
            'comadd' => $last->comadd,
            'comcity' => $last->comcity,
            'comnob' => $last->comnob,
            'bnkname' => $last->bnkname,
            'bnkbrn' => $last->bnkbrn,
        ])->assertRedirect(route('clients.index', [
            'q' => $token,
            'bank' => $bank,
            'page' => 2,
        ]).'#client-'.$last->recid);

        $this->put(route('clients.update', $last), [
            'form' => 'client-'.$last->recid,
            'q' => $token,
            'bank' => $bank,
            'comcode' => $last->comcode,
            'comname' => $last->comname,
            'comadd' => $last->comadd,
            'comcity' => $last->comcity,
            'comnob' => $last->comnob,
            'bnkname' => 'OTHER'.$token,
            'bnkbrn' => $last->bnkbrn,
        ])->assertRedirect(route('clients.index', [
            'q' => $token,
            'page' => 2,
        ]).'#client-'.$last->recid);
    }

    private function client(string $code, string $name, string $bank): Client
    {
        return Client::query()->create([
            'comcode' => $code,
            'comname' => $name,
            'comadd' => '12 Market Road',
            'comcity' => 'Manila',
            'comnob' => 'Wholesale',
            'bnkname' => $bank,
            'bnkbrn' => 'Makati',
        ]);
    }
}
