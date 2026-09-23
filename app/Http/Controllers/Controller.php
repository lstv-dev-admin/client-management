<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\DryRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected const PER_PAGE = 10;

    /** @var array<int, int> */
    protected const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    protected function validateForm(Request $request, array $rules, string $form, string $redirect, array $attributes = []): array
    {
        $validator = Validator::make($request->all(), $rules, [], $attributes);

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->errorBag($form)
                ->redirectTo($redirect);
        }

        return $validator->validated();
    }

    /**
     * @template T
     *
     * @param  callable(): T  $write
     * @param  callable(T): array<string, mixed>  $report
     * @param  callable(T): RedirectResponse  $success
     */
    protected function runWrite(callable $write, callable $report, callable $success): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $result = $write();
            $payload = $report($result);

            if (DryRun::enabled()) {
                DB::rollBack();

                return redirect()
                    ->to($this->indexUrl())
                    ->with('dry_run_report', $payload);
            }

            DB::commit();

            return $success($result);
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    protected function redirectToClient(Client $client, string $status): RedirectResponse
    {
        return redirect()
            ->to($this->indexUrl($client, 'client-'.$client->recid))
            ->with('status', $status);
    }

    protected function indexUrl(?Client $client = null, ?string $fragment = null): string
    {
        $search = trim((string) request()->input('q', ''));

        if ($client && $search !== '' && ! $this->clientMatchesSearch($client, $search)) {
            $search = '';
        }

        $params = [];

        if ($search !== '') {
            $params['q'] = $search;
        }

        $perPage = $this->perPage();

        if ($perPage !== self::PER_PAGE) {
            $params['per'] = $perPage;
        }

        $page = $client ? $this->pageFor($client, $search) : request()->integer('page');

        if ($page > 1) {
            $params['page'] = $page;
        }

        $url = route('clients.index', $params);

        if ($fragment) {
            $url .= '#'.ltrim($fragment, '#');
        }

        return $url;
    }

    protected function pageFor(Client $client, string $search): int
    {
        $trimmed = trim((string) $client->comname);
        $blank = $trimmed === '';

        $before = Client::query()
            ->search($search)
            ->where(function (Builder $query) use ($client, $blank, $trimmed) {
                if ($blank) {
                    $query->whereRaw("trim(coalesce(comname, '')) <> ''")
                        ->orWhere(function (Builder $query) use ($client) {
                            $query->whereRaw("trim(coalesce(comname, '')) = ''")
                                ->where('recid', '<', $client->recid);
                        });

                    return;
                }

                $query->whereRaw("trim(coalesce(comname, '')) <> ''")
                    ->where(function (Builder $query) use ($client, $trimmed) {
                        $query->whereRaw('trim(comname) < ?', [$trimmed])
                            ->orWhere(function (Builder $query) use ($client, $trimmed) {
                                $query->whereRaw('trim(comname) = ?', [$trimmed])
                                    ->where('recid', '<', $client->recid);
                            });
                    });
            })
            ->count();

        return intdiv($before, $this->perPage()) + 1;
    }

    protected function perPage(): int
    {
        $perPage = request()->integer('per');

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE;
    }

    protected function clientMatchesSearch(Client $client, string $search): bool
    {
        if (trim($search) === '') {
            return true;
        }

        return Client::query()->whereKey($client->getKey())->search($search)->exists();
    }
}
