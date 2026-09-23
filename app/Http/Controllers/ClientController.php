<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\SearchHighlighter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $search = trim((string) $request->query('q', ''));
        $perPage = $this->perPage();

        $clients = Client::query()
            ->with(['products', 'contacts'])
            ->search($search)
            ->orderedByName()
            ->paginate($perPage)
            ->withQueryString();

        if ($request->integer('page') > 1 && $clients->currentPage() > $clients->lastPage() && $clients->total() > 0) {
            return redirect()->route('clients.index', array_filter([
                'q' => $search !== '' ? $search : null,
                'per' => $perPage !== self::PER_PAGE ? $perPage : null,
                'page' => $clients->lastPage() > 1 ? $clients->lastPage() : null,
            ]));
        }

        return view('clients.index', [
            'clients' => $clients,
            'search' => $search,
            'perPage' => $perPage,
            'terms' => SearchHighlighter::terms($search),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $form = 'client-create';
        $rules = $this->rules();
        unset($rules['comcode']);
        $data = $this->validateForm($request, $rules, $form, $this->indexUrl(null, 'new-client'), Client::detailFields());

        return $this->runWrite(
            fn () => $this->createClient($data),
            function (Client $client) {
                return [
                    'title' => 'Create client',
                    'sections' => [[
                        'heading' => 'Company',
                        'items' => [[
                            'label' => filled($client->comname) ? $client->comname : $client->comcode,
                            'after' => $client->only(array_keys(Client::detailFields())),
                        ]],
                    ]],
                ];
            },
            fn (Client $client) => $this->redirectToClient($client, 'Client created.')
        );
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $form = 'client-'.$client->recid;
        $data = $this->validateForm(
            $request,
            $this->rules($client),
            $form,
            $this->indexUrl($client, 'client-'.$client->recid),
            Client::detailFields()
        );

        $before = $client->only(array_keys(Client::detailFields()));

        return $this->runWrite(
            function () use ($client, $data) {
                $client->update($data);

                return $client->refresh();
            },
            function (Client $updated) use ($before) {
                return [
                    'title' => 'Update client',
                    'sections' => [[
                        'heading' => 'Company',
                        'items' => [[
                            'label' => filled($updated->comname) ? $updated->comname : $updated->comcode,
                            'before' => $before,
                            'after' => $updated->only(array_keys(Client::detailFields())),
                        ]],
                    ]],
                ];
            },
            fn (Client $updated) => $this->redirectToClient($updated, 'Client updated.')
        );
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $search = trim((string) $request->input('q', ''));
        $page = $request->integer('page');
        $perPage = $this->perPage();
        $snapshot = $client->only(array_keys(Client::detailFields()));
        $productCount = $client->products()->count();
        $contactCount = $client->contacts()->count();
        $redirect = redirect()
            ->route('clients.index', array_filter([
                'q' => $search !== '' ? $search : null,
                'per' => $perPage !== self::PER_PAGE ? $perPage : null,
                'page' => $page > 1 ? $page : null,
            ]))
            ->with('status', 'Client deleted.');

        return $this->runWrite(
            function () use ($client) {
                $client->delete();

                return true;
            },
            function () use ($snapshot, $productCount, $contactCount) {
                return [
                    'title' => 'Delete client',
                    'sections' => [[
                        'heading' => 'Company',
                        'items' => [[
                            'label' => filled($snapshot['comname'] ?? null) ? $snapshot['comname'] : ($snapshot['comcode'] ?? 'Company'),
                            'before' => $snapshot,
                            'note' => "Would also remove {$productCount} product(s) and {$contactCount} contact(s).",
                        ]],
                    ]],
                ];
            },
            fn () => $redirect
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createClient(array $data): Client
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(function () use ($data) {
                    $data['comcode'] = Client::nextComcode();

                    return Client::query()->create($data);
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= 2) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Client $client = null): array
    {
        $unique = Rule::unique('clients', 'comcode');

        if ($client) {
            $unique->ignore($client->recid, 'recid');
        }

        return [
            'comcode' => ['required', 'string', 'max:255', $unique],
            'comname' => ['required', 'string', 'max:255'],
            'comadd' => ['required', 'string', 'max:255'],
            'comcity' => ['required', 'string', 'max:255'],
            'comnob' => ['required', 'string', 'max:255'],
            'bnkname' => ['required', 'string', 'max:255'],
            'bnkbrn' => ['required', 'string', 'max:255'],
        ];
    }
}
