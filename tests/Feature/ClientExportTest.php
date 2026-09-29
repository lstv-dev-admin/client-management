<?php

namespace Tests\Feature;

use App\Exports\ClientManagementExport;
use App\Models\Client;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class ClientExportTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var list<string>
     */
    private const HEADINGS = [
        'Company Name',
        'Type',
        'Bank Code',
        'Bank Branch',
        'Nature of Business',
        'Complete Address',
        'Product Name',
        'Product Version',
        'No. of License',
        'Contact Person',
        'Designation',
        'Contact No.',
        'Email',
    ];

    public function test_index_asks_for_a_bank_code_before_export(): void
    {
        $token = $this->token();

        Client::query()->create([
            'comcode' => 'XCODE-'.$token,
            'comname' => 'Bank Filter '.$token,
            'comadd' => '12 Market Road',
            'comcity' => 'Manila',
            'comnob' => 'Wholesale',
            'bnkname' => $token,
            'bnkbrn' => 'Main',
        ]);

        $this->get(route('clients.index', [
            'bank' => $token,
        ]))
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee('Select a bank')
            ->assertSee('Download')
            ->assertSee('action="'.route('clients.export').'"', false)
            ->assertSee('value="'.$token.'" selected', false);
    }

    public function test_export_groups_companies_products_and_contacts(): void
    {
        $token = $this->token();
        $hiddenCity = 'LOC-'.$token;

        $alpha = $this->makeClient($token, 'AAA', [
            'comadd' => '2F RMC Building, Montilla Blvd., Butuan City',
            'comnob' => 'BREEDING OF BROILER',
            'bnkname' => 'BPI',
            'bnkbrn' => 'BUTUAN MONTILLA',
        ], [
            ['prdname' => 'CS TKM EXP', 'prdvers' => 'Standard', 'prdnoli' => '3'],
            ['prdname' => 'CS PAYROLL STD', 'prdvers' => 'Standard', 'prdnoli' => '3'],
        ], [
            ['conperson' => 'JANE KHEE LABAS', 'condesig' => 'Accounting Manager', 'contactnum' => '0977-274-0230', 'conemail' => 'accounting.gov@philcoco.com'],
            ['conperson' => 'CAGALINGAN, MARYLENE P.', 'condesig' => 'Production Manager', 'contactnum' => '0917-153-4882', 'conemail' => 'NINGCAGALINGAN@PHILCOCO.COM'],
        ], $hiddenCity, $token);

        $bravo = $this->makeClient($token, 'BBB', [
            'comadd' => 'Makati Avenue',
            'comnob' => 'Manufacturing',
            'bnkname' => 'BDO',
            'bnkbrn' => 'MAKATI',
        ], [
            ['prdname' => 'Gamma', 'prdvers' => 'Premium', 'prdnoli' => '2'],
            ['prdname' => 'Alpha', 'prdvers' => 'Standard', 'prdnoli' => '3'],
            ['prdname' => 'Beta', 'prdvers' => 'Standard', 'prdnoli' => '5'],
        ], [
            ['conperson' => 'Bea', 'condesig' => 'Accountant', 'contactnum' => '0918', 'conemail' => 'b@email.com'],
            ['conperson' => 'Ann', 'condesig' => 'Manager', 'contactnum' => '0917', 'conemail' => 'a@email.com'],
        ], $hiddenCity, $token);

        $charlie = $this->makeClient($token, 'CCC', [
            'comadd' => 'Cebu Port',
            'comnob' => 'Logistics',
            'bnkname' => 'MBTC',
            'bnkbrn' => 'CEBU',
        ], [
            ['prdname' => 'Beta', 'prdvers' => 'Standard', 'prdnoli' => '1'],
            ['prdname' => 'Alpha', 'prdvers' => 'Premium', 'prdnoli' => '4'],
        ], [
            ['conperson' => 'Dee', 'condesig' => 'Clerk', 'contactnum' => '0914', 'conemail' => 'd@email.com'],
            ['conperson' => 'Ann', 'condesig' => 'Lead', 'contactnum' => '0911', 'conemail' => 'a@email.com'],
            ['conperson' => 'Cara', 'condesig' => 'Analyst', 'contactnum' => '0913', 'conemail' => 'c@email.com'],
            ['conperson' => 'Bea', 'condesig' => 'Accountant', 'contactnum' => '0912', 'conemail' => 'b@email.com'],
        ], $hiddenCity, $token);

        $delta = $this->makeClient($token, 'DDD', [
            'comadd' => 'Davao Wharf',
            'comnob' => 'Shipping',
            'bnkname' => 'BPI',
            'bnkbrn' => 'DAVAO',
        ], [
            ['prdname' => 'Beta', 'prdvers' => 'Standard', 'prdnoli' => '8'],
            ['prdname' => 'Alpha', 'prdvers' => null, 'prdnoli' => null],
        ], [], $hiddenCity, $token);

        $echo = $this->makeClient($token, 'EEE', [
            'comadd' => 'Iloilo Road',
            'comnob' => 'Retail',
            'bnkname' => 'BDO',
            'bnkbrn' => 'ILOILO',
        ], [], [
            ['conperson' => 'Zed Person', 'condesig' => 'Staff', 'contactnum' => '0999', 'conemail' => 'z@email.com'],
            ['conperson' => 'Ann Person', 'condesig' => 'Lead', 'contactnum' => '09171234567', 'conemail' => 'a@email.com'],
        ], $hiddenCity, $token);

        $foxtrot = $this->makeClient($token, 'FFF', [
            'comadd' => 'Quiet Street',
            'comnob' => 'Holding',
            'bnkname' => 'BPI',
            'bnkbrn' => 'QUIET',
        ], [], [], $hiddenCity, $token);

        $golf = $this->makeClient($token, 'GGG', [
            'comadd' => 'Shared Lane',
            'comnob' => 'Services',
            'bnkname' => 'BDO',
            'bnkbrn' => 'SHARED',
        ], [
            ['prdname' => 'Shared Product', 'prdvers' => 'older', 'prdnoli' => '1'],
            ['prdname' => 'Shared Product', 'prdvers' => 'newer', 'prdnoli' => '2'],
        ], [], $hiddenCity, $token);

        $otherBank = $this->makeClient($token, 'ZZZ', [
            'comadd' => 'Other Bank Road',
            'comnob' => 'Other',
            'bnkname' => $token.'-OTHER',
            'bnkbrn' => 'OTHER',
        ], [], [], $hiddenCity);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('clients.export', [
            'bank' => $token,
            'page' => 2,
            'per' => 10,
        ]));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertDownload(ClientManagementExport::FILENAME);

        $sheet = $this->sheet($response);
        $rows = $this->rows($sheet);

        $this->assertSame(self::HEADINGS, $rows[0]);
        $this->assertSame([
            $this->companyRow($alpha, 'CS PAYROLL STD', 'Standard', '3', 'CAGALINGAN, MARYLENE P.', 'Production Manager', '0917-153-4882', 'NINGCAGALINGAN@PHILCOCO.COM'),
            $this->blankCompanyRow('CS TKM EXP', 'Standard', '3', 'JANE KHEE LABAS', 'Accounting Manager', '0977-274-0230', 'accounting.gov@philcoco.com'),
            $this->companyRow($bravo, 'Alpha', 'Standard', '3', 'Ann', 'Manager', '0917', 'a@email.com'),
            $this->blankCompanyRow('Beta', 'Standard', '5', 'Bea', 'Accountant', '0918', 'b@email.com'),
            $this->blankCompanyRow('Gamma', 'Premium', '2'),
            $this->companyRow($charlie, 'Alpha', 'Premium', '4', 'Ann', 'Lead', '0911', 'a@email.com'),
            $this->blankCompanyRow('Beta', 'Standard', '1', 'Bea', 'Accountant', '0912', 'b@email.com'),
            $this->blankCompanyRow('', '', '', 'Cara', 'Analyst', '0913', 'c@email.com'),
            $this->blankCompanyRow('', '', '', 'Dee', 'Clerk', '0914', 'd@email.com'),
            $this->companyRow($delta, 'Alpha', '', ''),
            $this->blankCompanyRow('Beta', 'Standard', '8'),
            $this->companyRow($echo, '', '', '', 'Ann Person', 'Lead', '09171234567', 'a@email.com'),
            $this->blankCompanyRow('', '', '', 'Zed Person', 'Staff', '0999', 'z@email.com'),
            $this->companyRow($foxtrot),
            $this->companyRow($golf, 'Shared Product', 'older', '1'),
            $this->blankCompanyRow('Shared Product', 'newer', '2'),
        ], array_slice($rows, 1));

        $this->assertSame('M', $sheet->getHighestColumn());
        $this->assertNull($sheet->getCell('N1')->getValue());
        $this->assertSame([], $sheet->getMergeCells());
        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        $this->assertTrue($sheet->getStyle('M1')->getFont()->getBold());
        $this->assertSame('A1:M'.$sheet->getHighestRow(), $sheet->getAutoFilter()->getRange());
        $this->assertGreaterThan(0, $sheet->getColumnDimension('A')->getWidth());
        $this->assertGreaterThan(0, $sheet->getColumnDimension('F')->getWidth());

        $phone = $sheet->getCell('L13');
        $this->assertSame(DataType::TYPE_STRING, $phone->getDataType());
        $this->assertSame('09171234567', $phone->getValue());

        $flat = implode("\n", array_map(fn (array $row) => implode("\t", $row), $rows));
        $this->assertStringNotContainsString($hiddenCity, $flat);
        $this->assertStringNotContainsString('XCODE-'.$token, $flat);
        $this->assertStringNotContainsString((string) $alpha->comcode, $flat);
        $this->assertStringNotContainsString($otherBank->comname, $flat);

        $productQueries = collect($queries)->filter(
            fn (array $query) => str_contains(strtolower($query['query']), 'client_products')
        )->count();
        $contactQueries = collect($queries)->filter(
            fn (array $query) => str_contains(strtolower($query['query']), 'client_contacts')
        )->count();

        $this->assertLessThanOrEqual(2, $productQueries);
        $this->assertLessThanOrEqual(2, $contactQueries);
        $this->assertLessThanOrEqual(8, count($queries));
    }

    public function test_export_without_a_bank_code_is_rejected(): void
    {
        $this->get(route('clients.export'))
            ->assertRedirect(route('clients.index'))
            ->assertSessionHasErrors('bank');

        $this->get(route('clients.export', ['bank' => 'not-a-real-bank']))
            ->assertRedirect(route('clients.index'))
            ->assertSessionHasErrors('bank');
    }

    private function token(): string
    {
        return 'exp'.bin2hex(random_bytes(4));
    }

    /**
     * @param  array<string, string>  $company
     * @param  list<array<string, string|null>>  $products
     * @param  list<array<string, string>>  $contacts
     */
    private function makeClient(string $token, string $suffix, array $company, array $products, array $contacts, string $hiddenCity, ?string $bank = null): Client
    {
        $client = Client::query()->create([
            'comcode' => 'XCODE-'.$token.'-'.$suffix,
            'comname' => $token.' '.$suffix,
            'comadd' => $company['comadd'],
            'comcity' => $hiddenCity,
            'comnob' => $company['comnob'],
            'bnkname' => $bank ?? $company['bnkname'],
            'bnkbrn' => $company['bnkbrn'],
        ]);

        foreach ($products as $product) {
            $client->products()->create($product);
        }

        foreach ($contacts as $contact) {
            $client->contacts()->create($contact);
        }

        return $client;
    }

    /**
     * @return list<string>
     */
    private function companyRow(
        Client $client,
        string $product = '',
        string $version = '',
        string $licenses = '',
        string $person = '',
        string $designation = '',
        string $phone = '',
        string $email = '',
    ): array {
        return [
            $client->comname,
            'Client',
            $client->bnkname,
            $client->bnkbrn,
            $client->comnob,
            $client->comadd,
            $product,
            $version,
            $licenses,
            $person,
            $designation,
            $phone,
            $email,
        ];
    }

    /**
     * @return list<string>
     */
    private function blankCompanyRow(
        string $product = '',
        string $version = '',
        string $licenses = '',
        string $person = '',
        string $designation = '',
        string $phone = '',
        string $email = '',
    ): array {
        return [
            '',
            '',
            '',
            '',
            '',
            '',
            $product,
            $version,
            $licenses,
            $person,
            $designation,
            $phone,
            $email,
        ];
    }

    private function sheet(TestResponse $response): Worksheet
    {
        $response->assertOk();

        $file = $response->baseResponse->getFile();
        $spreadsheet = IOFactory::load($file->getPathname());

        return $spreadsheet->getActiveSheet();
    }

    /**
     * @return list<list<string>>
     */
    private function rows(Worksheet $sheet): array
    {
        $matrix = $sheet->rangeToArray('A1:M'.$sheet->getHighestRow(), null, true, false, false);

        return array_map(function (array $row): array {
            $normalized = [];

            for ($index = 0; $index < 13; $index++) {
                $normalized[] = $this->cell($row[$index] ?? null);
            }

            return $normalized;
        }, $matrix);
    }

    private function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) == $value) {
            return (string) (int) $value;
        }

        return (string) $value;
    }
}
