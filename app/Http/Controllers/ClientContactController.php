<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientContactController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $form = 'contact-new-'.$client->recid;
        $data = $this->validateForm($request, $this->rules(), $form, $this->indexUrl($client, 'client-'.$client->recid), ClientContact::detailFields());

        $client->contacts()->create($data);

        return $this->redirectToClient($client, 'Contact added.');
    }

    public function update(Request $request, Client $client, ClientContact $contact): RedirectResponse
    {
        abort_unless($contact->comcode === $client->comcode, 404);

        $form = 'contact-'.$contact->recid;
        $data = $this->validateForm($request, $this->rules(), $form, $this->indexUrl($client, 'client-'.$client->recid), ClientContact::detailFields());

        $contact->update($data);

        return $this->redirectToClient($client, 'Contact updated.');
    }

    public function destroy(Client $client, ClientContact $contact): RedirectResponse
    {
        abort_unless($contact->comcode === $client->comcode, 404);

        $contact->delete();

        return $this->redirectToClient($client, 'Contact deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'conperson' => ['required', 'string', 'max:255'],
            'condesig' => ['required', 'string', 'max:255'],
            'contactnum' => ['required', 'string', 'max:255'],
            'conemail' => ['required', 'email', 'max:255'],
        ];
    }
}
