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
        ], $form, $this->indexUrl($client, 'client-'.$client->recid), [
            'prdname' => 'Product Name',
        ]);

        $client->products()->create($data);

        return $this->redirectToClient($client, 'Product added.');
    }

    public function update(Request $request, Client $client, ClientProduct $product): RedirectResponse
    {
        abort_unless($product->comcode === $client->comcode, 404);

        $form = 'product-'.$product->recid;
        $data = $this->validateForm($request, [
            'prdname' => ['required', 'string', 'max:255'],
        ], $form, $this->indexUrl($client, 'client-'.$client->recid), [
            'prdname' => 'Product Name',
        ]);

        $product->update($data);

        return $this->redirectToClient($client, 'Product updated.');
    }

    public function destroy(Client $client, ClientProduct $product): RedirectResponse
    {
        abort_unless($product->comcode === $client->comcode, 404);

        $product->delete();

        return $this->redirectToClient($client, 'Product deleted.');
    }
}
