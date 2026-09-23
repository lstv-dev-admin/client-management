<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientProduct;
use App\Support\SearchHighlighter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientMergeController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $clients = Client::query()
            ->with(['products', 'contacts'])
            ->search($term)
            ->when($request->filled('except'), function ($query) use ($request) {
                $query->where('recid', '!=', $request->integer('except'));
            })
            ->orderedByName()
            ->limit(8)
            ->get();

        $words = SearchHighlighter::terms($term);

        return response()->json($clients->map(fn (Client $client) => [
            'recid' => $client->recid,
            'comcode' => $client->comcode,
            'comname' => $client->comname,
            'hint' => $this->matchHint($client, $words),
        ]));
    }

    /**
     * @param  array<int, string>  $words
     */
    private function matchHint(Client $client, array $words): ?string
    {
        $visible = mb_strtolower(trim((string) $client->comname.' '.(string) $client->comcode));

        foreach ($words as $word) {
            $needle = mb_strtolower($word);

            if (str_contains($visible, $needle)) {
                continue;
            }

            foreach ($client->products as $product) {
                if (str_contains(mb_strtolower((string) $product->prdname), $needle)) {
                    return 'Product: '.$product->prdname;
                }

                if (str_contains(mb_strtolower((string) $product->prdvers), $needle)) {
                    return 'Product Version: '.$product->prdvers;
                }

                if (str_contains(mb_strtolower((string) $product->prdnoli), $needle)) {
                    return 'Product Number of License: '.$product->prdnoli;
                }
            }

            foreach ($client->contacts as $contact) {
                $fields = [
                    'conperson' => 'Contact',
                    'condesig' => 'Designation',
                    'contactnum' => 'Contact No.',
                    'conemail' => 'Email',
                ];

                foreach ($fields as $field => $label) {
                    if (str_contains(mb_strtolower((string) $contact->{$field}), $needle)) {
                        return $label.': '.$contact->{$field};
                    }
                }
            }

            $companyFields = [
                'comadd' => 'Address',
                'comcity' => 'Location',
                'comnob' => 'Nature of Business',
                'bnkname' => 'Bank Code',
                'bnkbrn' => 'Bank Branch',
            ];

            foreach ($companyFields as $field => $label) {
                if (str_contains(mb_strtolower((string) $client->{$field}), $needle)) {
                    return $label.': '.$client->{$field};
                }
            }
        }

        return null;
    }

    public function summary(Client $client): JsonResponse
    {
        $client->load(['products', 'contacts']);

        return response()->json([
            'recid' => $client->recid,
            'comcode' => $client->comcode,
            'comname' => $client->comname,
            'products' => $client->products->map(fn (ClientProduct $product) => [
                'recid' => $product->recid,
                'prdname' => $product->prdname,
                'prdnoli' => $product->prdnoli,
                'prdvers' => $product->prdvers,
            ])->values(),
            'contacts' => $client->contacts->map(fn (ClientContact $contact) => [
                'recid' => $contact->recid,
                'conperson' => $contact->conperson,
                'condesig' => $contact->condesig,
                'contactnum' => $contact->contactnum,
                'conemail' => $contact->conemail,
            ])->values(),
        ]);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $data = $this->validateMerge($request, $client);
        $target = Client::query()->with('products')->findOrFail($data['target']);
        $productIds = array_values(array_unique(array_map('intval', $data['products'] ?? [])));
        $contactIds = array_values(array_unique(array_map('intval', $data['contacts'] ?? [])));
        $combineIds = array_values(array_unique(array_map('intval', $data['combine_products'] ?? [])));
        $form = 'merge-'.$client->recid;
        $redirect = $this->indexUrl($client, 'client-'.$client->recid);

        return $this->runWrite(
            function () use ($client, $target, $productIds, $contactIds, $combineIds, $form, $redirect) {
                [$moveIds, $combinePairs, $skipped] = $this->partitionProducts($client, $target, $productIds, $combineIds, $form, $redirect);

                $combineReport = [];

                foreach ($combinePairs as $pair) {
                    $combineReport[] = [
                        'name' => $pair['source']->prdname,
                        'before_source' => [
                            'Version' => $pair['source']->prdvers,
                            'License' => $pair['source']->prdnoli,
                        ],
                        'before_target' => [
                            'Version' => $pair['target']->prdvers,
                            'License' => $pair['target']->prdnoli,
                        ],
                        'after_license' => $this->sumLicenses($pair['source']->prdnoli, $pair['target']->prdnoli),
                    ];
                }

                $movedProducts = ClientProduct::query()
                    ->where('comcode', $client->comcode)
                    ->whereIn('recid', $moveIds)
                    ->get()
                    ->map(fn (ClientProduct $product) => $product->only(['prdname', 'prdvers', 'prdnoli']))
                    ->all();

                $movedContacts = ClientContact::query()
                    ->where('comcode', $client->comcode)
                    ->whereIn('recid', $contactIds)
                    ->get()
                    ->map(fn (ClientContact $contact) => $contact->only(array_keys(ClientContact::detailFields())))
                    ->all();

                $this->combineProducts($combinePairs);
                $this->moveRecords(ClientProduct::class, $client, $target, $moveIds, 'products', 'Select products from this company only.', $form, $redirect);
                $this->moveRecords(ClientContact::class, $client, $target, $contactIds, 'contacts', 'Select contacts from this company only.', $form, $redirect);

                return [
                    'source' => $client->only(['comcode', 'comname']),
                    'target' => $target->only(['comcode', 'comname']),
                    'combined' => $combineReport,
                    'moved_products' => $movedProducts,
                    'moved_contacts' => $movedContacts,
                    'skipped' => collect($skipped)->map(fn (ClientProduct $product) => $product->only(['prdname', 'prdvers', 'prdnoli']))->all(),
                ];
            },
            function (array $result) {
                $sections = [];

                if ($result['combined'] !== []) {
                    $sections[] = [
                        'heading' => 'Combined products',
                        'items' => collect($result['combined'])->map(fn (array $row) => [
                            'label' => $row['name'],
                            'before' => [
                                'A Version' => $row['before_source']['Version'],
                                'A License' => $row['before_source']['License'],
                                'B Version' => $row['before_target']['Version'],
                                'B License' => $row['before_target']['License'],
                            ],
                            'after' => [
                                'Version' => null,
                                'License' => $row['after_license'],
                            ],
                            'note' => 'Company A copy would be removed.',
                        ])->all(),
                    ];
                }

                if ($result['moved_products'] !== []) {
                    $sections[] = [
                        'heading' => 'Moved products',
                        'items' => collect($result['moved_products'])->map(fn (array $row) => [
                            'label' => $row['prdname'],
                            'after' => $row,
                        ])->all(),
                    ];
                }

                if ($result['skipped'] !== []) {
                    $sections[] = [
                        'heading' => 'Skipped same-name products',
                        'items' => collect($result['skipped'])->map(fn (array $row) => [
                            'label' => $row['prdname'],
                            'before' => $row,
                            'note' => 'Would stay on Company A.',
                        ])->all(),
                    ];
                }

                if ($result['moved_contacts'] !== []) {
                    $sections[] = [
                        'heading' => 'Moved contacts',
                        'items' => collect($result['moved_contacts'])->map(fn (array $row) => [
                            'label' => $row['conperson'],
                            'after' => $row,
                        ])->all(),
                    ];
                }

                return [
                    'title' => 'Merge '
                        .(filled($result['source']['comname']) ? $result['source']['comname'] : $result['source']['comcode'])
                        .' → '
                        .(filled($result['target']['comname']) ? $result['target']['comname'] : $result['target']['comcode']),
                    'sections' => $sections,
                ];
            },
            fn () => $this->redirectToClient($client, 'Products and contacts moved.')
                ->with('merge_delete', $client->recid)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMerge(Request $request, Client $client): array
    {
        $form = 'merge-'.$client->recid;
        $validator = Validator::make($request->all(), [
            'target' => ['required', 'integer', 'exists:clients,recid', Rule::notIn([$client->recid])],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer'],
            'combine_products' => ['nullable', 'array'],
            'combine_products.*' => ['integer'],
            'contacts' => ['nullable', 'array'],
            'contacts.*' => ['integer'],
        ], [
            'target.not_in' => 'Choose a different company.',
            'target.exists' => 'Choose a company from the list.',
        ], [
            'target' => 'company',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $products = array_filter((array) $request->input('products', []), fn ($id) => $id !== '' && $id !== null);
            $contacts = array_filter((array) $request->input('contacts', []), fn ($id) => $id !== '' && $id !== null);

            if ($products === [] && $contacts === []) {
                $validator->errors()->add('products', 'Select at least one product or contact.');
            }
        });

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->errorBag($form)
                ->redirectTo($this->indexUrl($client, 'client-'.$client->recid));
        }

        return $validator->validated();
    }

    /**
     * @param  array<int, int>  $productIds
     * @param  array<int, int>  $combineIds
     * @return array{0: array<int, int>, 1: array<int, array{source: ClientProduct, target: ClientProduct}>, 2: array<int, ClientProduct>}
     */
    private function partitionProducts(Client $source, Client $target, array $productIds, array $combineIds, string $form, string $redirect): array
    {
        if ($productIds === []) {
            if ($combineIds !== []) {
                $this->failMerge('combine_products', 'Choose products to combine from the selected list.', $form, $redirect);
            }

            return [[], [], []];
        }

        $sourceProducts = ClientProduct::query()
            ->where('comcode', $source->comcode)
            ->whereIn('recid', $productIds)
            ->get()
            ->keyBy('recid');

        if ($sourceProducts->count() !== count($productIds)) {
            $this->failMerge('products', 'Select products from this company only.', $form, $redirect);
        }

        $targetByName = [];

        foreach ($target->products as $product) {
            $key = $this->productNameKey($product->prdname);

            if ($key !== '' && ! array_key_exists($key, $targetByName)) {
                $targetByName[$key] = $product;
            }
        }

        $combineSet = array_fill_keys($combineIds, true);
        $moveIds = [];
        $combinePairs = [];
        $skipped = [];

        foreach ($productIds as $productId) {
            /** @var ClientProduct $sourceProduct */
            $sourceProduct = $sourceProducts->get($productId);
            $key = $this->productNameKey($sourceProduct->prdname);
            $match = $key !== '' ? ($targetByName[$key] ?? null) : null;

            if ($match === null) {
                if (isset($combineSet[$productId])) {
                    $this->failMerge('combine_products', 'Only matching product names can be combined.', $form, $redirect);
                }

                $moveIds[] = $productId;

                continue;
            }

            if (isset($combineSet[$productId])) {
                $combinePairs[] = [
                    'source' => $sourceProduct,
                    'target' => $match,
                ];
                unset($combineSet[$productId]);
            } else {
                $skipped[] = $sourceProduct;
            }
        }

        if ($combineSet !== []) {
            $this->failMerge('combine_products', 'Choose products to combine from the selected list.', $form, $redirect);
        }

        return [$moveIds, $combinePairs, $skipped];
    }

    /**
     * @param  array<int, array{source: ClientProduct, target: ClientProduct}>  $pairs
     */
    private function combineProducts(array $pairs): void
    {
        foreach ($pairs as $pair) {
            $pair['target']->update([
                'prdnoli' => $this->sumLicenses($pair['source']->prdnoli, $pair['target']->prdnoli),
                'prdvers' => null,
            ]);

            $pair['source']->delete();
        }
    }

    private function sumLicenses(mixed $left, mixed $right): ?string
    {
        $leftValue = $this->wholeLicenseNumber($left);
        $rightValue = $this->wholeLicenseNumber($right);

        if ($leftValue === null || $rightValue === null) {
            return null;
        }

        return (string) ($leftValue + $rightValue);
    }

    private function wholeLicenseNumber(mixed $value): ?int
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '' || preg_match('/^\d+$/', $trimmed) !== 1) {
            return null;
        }

        return (int) $trimmed;
    }

    private function productNameKey(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    private function failMerge(string $field, string $message, string $form, string $redirect): never
    {
        $exception = ValidationException::withMessages([
            $field => $message,
        ]);
        $exception->errorBag($form);
        $exception->redirectTo($redirect);

        throw $exception;
    }

    /**
     * @param  class-string<ClientProduct|ClientContact>  $model
     * @param  array<int, int|string>  $ids
     */
    private function moveRecords(string $model, Client $source, Client $target, array $ids, string $field, string $message, string $form, string $redirect): void
    {
        if ($ids === []) {
            return;
        }

        $updated = $model::query()
            ->where('comcode', $source->comcode)
            ->whereIn('recid', $ids)
            ->update(['comcode' => $target->comcode]);

        if ($updated !== count($ids)) {
            $this->failMerge($field, $message, $form, $redirect);
        }
    }
}
