<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientProductController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $form = 'product-new-'.$client->recid;
        $data = $this->validateForm($request, [
            'prdname' => ['required', 'string', 'max:255'],
            'prdnoli' => ['nullable', 'string', 'max:255'],
            'prdvers' => ['nullable', 'string', 'max:255'],
        ], $form, $this->indexUrl($client, 'client-'.$client->recid), ClientProduct::detailFields());

        return $this->runWrite(
            fn () => $client->products()->create($data),
            function (ClientProduct $product) {
                return [
                    'title' => 'Add product',
                    'sections' => [[
                        'heading' => 'Product',
                        'items' => [[
                            'label' => $product->prdname,
                            'after' => $product->only(array_keys(ClientProduct::detailFields())),
                        ]],
                    ]],
                ];
            },
            fn () => $this->redirectToClient($client, 'Product added.')
        );
    }

    public function update(Request $request, Client $client, ClientProduct $product): RedirectResponse
    {
        abort_unless($product->comcode === $client->comcode, 404);

        $form = 'product-'.$product->recid;
        $data = $this->validateForm($request, [
            'prdname' => ['required', 'string', 'max:255'],
            'prdnoli' => ['nullable', 'string', 'max:255'],
            'prdvers' => ['nullable', 'string', 'max:255'],
        ], $form, $this->indexUrl($client, 'client-'.$client->recid), ClientProduct::detailFields());

        $before = $product->only(array_keys(ClientProduct::detailFields()));

        return $this->runWrite(
            function () use ($product, $data) {
                $product->update($data);

                return $product->refresh();
            },
            function (ClientProduct $updated) use ($before) {
                return [
                    'title' => 'Update product',
                    'sections' => [[
                        'heading' => 'Product',
                        'items' => [[
                            'label' => $updated->prdname,
                            'before' => $before,
                            'after' => $updated->only(array_keys(ClientProduct::detailFields())),
                        ]],
                    ]],
                ];
            },
            fn () => $this->redirectToClient($client, 'Product updated.')
        );
    }

    public function destroy(Client $client, ClientProduct $product): RedirectResponse
    {
        abort_unless($product->comcode === $client->comcode, 404);

        $before = $product->only(array_keys(ClientProduct::detailFields()));

        return $this->runWrite(
            function () use ($product) {
                $product->delete();

                return true;
            },
            function () use ($before) {
                return [
                    'title' => 'Delete product',
                    'sections' => [[
                        'heading' => 'Product',
                        'items' => [[
                            'label' => $before['prdname'] ?? 'Product',
                            'before' => $before,
                        ]],
                    ]],
                ];
            },
            fn () => $this->redirectToClient($client, 'Product deleted.')
        );
    }
}
