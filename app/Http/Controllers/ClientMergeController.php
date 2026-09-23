<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientProduct;
use App\Support\SearchHighlighter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $target = Client::query()->findOrFail($data['target']);
        $productIds = array_values(array_unique($data['products'] ?? []));
        $contactIds = array_values(array_unique($data['contacts'] ?? []));
        $form = 'merge-'.$client->recid;
        $redirect = $this->indexUrl($client, 'client-'.$client->recid);

        DB::transaction(function () use ($client, $target, $productIds, $contactIds, $form, $redirect) {
            $this->moveRecords(ClientProduct::class, $client, $target, $productIds, 'products', 'Select products from this company only.', $form, $redirect);
            $this->moveRecords(ClientContact::class, $client, $target, $contactIds, 'contacts', 'Select contacts from this company only.', $form, $redirect);
        });

        return $this->redirectToClient($client, 'Products and contacts moved.')
            ->with('merge_delete', $client->recid);
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
            $exception = ValidationException::withMessages([
                $field => $message,
            ]);
            $exception->errorBag($form);
            $exception->redirectTo($redirect);

            throw $exception;
        }
    }
}
