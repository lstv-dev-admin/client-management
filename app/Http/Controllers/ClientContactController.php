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

        return $this->runWrite(
            fn () => $client->contacts()->create($data),
            function (ClientContact $contact) {
                return [
                    'title' => 'Add contact',
                    'sections' => [[
                        'heading' => 'Contact',
                        'items' => [[
                            'label' => $contact->conperson,
                            'after' => $contact->only(array_keys(ClientContact::detailFields())),
                        ]],
                    ]],
                ];
            },
            fn () => $this->redirectToClient($client, 'Contact added.')
        );
    }

    public function update(Request $request, Client $client, ClientContact $contact): RedirectResponse
    {
        abort_unless($contact->comcode === $client->comcode, 404);

        $form = 'contact-'.$contact->recid;
        $data = $this->validateForm($request, $this->rules(), $form, $this->indexUrl($client, 'client-'.$client->recid), ClientContact::detailFields());

        $before = $contact->only(array_keys(ClientContact::detailFields()));

        return $this->runWrite(
            function () use ($contact, $data) {
                $contact->update($data);

                return $contact->refresh();
            },
            function (ClientContact $updated) use ($before) {
                return [
                    'title' => 'Update contact',
                    'sections' => [[
                        'heading' => 'Contact',
                        'items' => [[
                            'label' => $updated->conperson,
                            'before' => $before,
                            'after' => $updated->only(array_keys(ClientContact::detailFields())),
                        ]],
                    ]],
                ];
            },
            fn () => $this->redirectToClient($client, 'Contact updated.')
        );
    }

    public function destroy(Client $client, ClientContact $contact): RedirectResponse
    {
        abort_unless($contact->comcode === $client->comcode, 404);

        $before = $contact->only(array_keys(ClientContact::detailFields()));

        return $this->runWrite(
            function () use ($contact) {
                $contact->delete();

                return true;
            },
            function () use ($before) {
                return [
                    'title' => 'Delete contact',
                    'sections' => [[
                        'heading' => 'Contact',
                        'items' => [[
                            'label' => $before['conperson'] ?? 'Contact',
                            'before' => $before,
                        ]],
                    ]],
                ];
            },
            fn () => $this->redirectToClient($client, 'Contact deleted.')
        );
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
