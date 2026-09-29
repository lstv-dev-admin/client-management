<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Columns\Column;
use Maatwebsite\Excel\Columns\Text;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumns;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientManagementExport implements FromArray, WithColumns, WithFreezePane, WithStyles
{
    public const FILENAME = 'client-management-export.xlsx';

    /**
     * @param  Collection<int, Client>  $clients
     */
    public function __construct(private Collection $clients) {}

    /**
     * @return array<int, array<string, string>>
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->clients as $client) {
            $products = $client->products->values();
            $contacts = $client->contacts->values();
            $rowCount = max($products->count(), $contacts->count(), 1);

            for ($index = 0; $index < $rowCount; $index++) {
                $rows[] = $this->row(
                    $index === 0 ? $client : null,
                    $products->get($index),
                    $contacts->get($index),
                );
            }
        }

        return $rows;
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            $this->column('Company Name', 'company_name', 36),
            $this->column('Type', 'type', 12),
            $this->column('Bank Code', 'bank_code', 16),
            $this->column('Bank Branch', 'bank_branch', 22),
            $this->column('Nature of Business', 'nature_of_business', 28),
            $this->column('Complete Address', 'complete_address', 48),
            $this->column('Product Name', 'product_name', 22),
            $this->column('Product Version', 'product_version', 18),
            $this->column('No. of License', 'license_count', 16),
            $this->column('Contact Person', 'contact_person', 32),
            $this->column('Designation', 'designation', 24),
            $this->column('Contact No.', 'contact_no', 18, true),
            $this->column('Email', 'email', 36),
        ];
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function column(string $heading, string $attribute, float $width, bool $text = false): Column
    {
        $column = $text ? Text::make($heading, $attribute) : Column::make($heading, $attribute);

        return $column->width($width)->autoFilter();
    }

    /**
     * @return array<string, string>
     */
    private function row(?Client $client, ?ClientProduct $product, ?ClientContact $contact): array
    {
        return [
            'company_name' => $client ? $this->cell($client->comname) : '',
            'type' => $client ? 'Client' : '',
            'bank_code' => $client ? $this->cell($client->bnkname) : '',
            'bank_branch' => $client ? $this->cell($client->bnkbrn) : '',
            'nature_of_business' => $client ? $this->cell($client->comnob) : '',
            'complete_address' => $client ? $this->cell($client->comadd) : '',
            'product_name' => $product ? $this->cell($product->prdname) : '',
            'product_version' => $product ? $this->cell($product->prdvers) : '',
            'license_count' => $product ? $this->cell($product->prdnoli) : '',
            'contact_person' => $contact ? $this->cell($contact->conperson) : '',
            'designation' => $contact ? $this->cell($contact->condesig) : '',
            'contact_no' => $contact ? $this->cell($contact->contactnum) : '',
            'email' => $contact ? $this->cell($contact->conemail) : '',
        ];
    }

    private function cell(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }
}
