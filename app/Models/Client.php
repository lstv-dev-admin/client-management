<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Client extends Model
{
    protected $primaryKey = 'recid';

    protected $fillable = [
        'comcode',
        'comname',
        'comadd',
        'comcity',
        'comnob',
        'bnkname',
        'bnkbrn',
    ];

    public static function detailFields(): array
    {
        return [
            'comcode' => 'Company Code',
            'comname' => 'Company Name',
            'comadd' => 'Complete Address',
            'comcity' => 'Location',
            'comnob' => 'Nature of Business',
            'bnkname' => 'Bank Code',
            'bnkbrn' => 'Bank Branch',
        ];
    }

    public static function comcodeFromNumber(int $number): string
    {
        return 'COM-'.str_pad((string) $number, 9, '0', STR_PAD_LEFT);
    }

    public static function nextComcode(): string
    {
        $latest = static::query()
            ->whereRaw("comcode REGEXP '^COM-[0-9]+$'")
            ->orderByRaw('CAST(SUBSTRING(comcode, 5) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->value('comcode');

        $number = 0;

        if (is_string($latest) && preg_match('/^COM-(\d+)$/', $latest, $matches) === 1) {
            $number = (int) $matches[1];
        }

        return static::comcodeFromNumber($number + 1);
    }

    public function products(): HasMany
    {
        return $this->hasMany(ClientProduct::class, 'comcode', 'comcode')->orderBy('prdname');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class, 'comcode', 'comcode')->orderBy('conperson');
    }

    public function scopeSearch($query, string $search)
    {
        $words = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return $query;
        }

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '\\%_').'%';

            $query->where(function ($query) use ($like) {
                $query->where('comname', 'like', $like)
                    ->orWhere('comcode', 'like', $like)
                    ->orWhere('comadd', 'like', $like)
                    ->orWhere('comcity', 'like', $like)
                    ->orWhere('comnob', 'like', $like)
                    ->orWhere('bnkname', 'like', $like)
                    ->orWhere('bnkbrn', 'like', $like)
                    ->orWhereHas('products', function ($query) use ($like) {
                        $query->where('prdname', 'like', $like)
                            ->orWhere('prdnoli', 'like', $like)
                            ->orWhere('prdvers', 'like', $like);
                    })
                    ->orWhereHas('contacts', function ($query) use ($like) {
                        $query->where('conperson', 'like', $like)
                            ->orWhere('condesig', 'like', $like)
                            ->orWhere('contactnum', 'like', $like)
                            ->orWhere('conemail', 'like', $like);
                    });
            });
        }

        return $query;
    }

    public function scopeOrderedByName($query)
    {
        return $query
            ->orderByRaw("case when trim(coalesce(comname, '')) = '' then 1 else 0 end")
            ->orderByRaw('trim(comname)')
            ->orderBy('recid');
    }

    /**
     * @return Collection<int, string>
     */
    public static function bankCodes(): Collection
    {
        $codes = static::query()
            ->whereRaw("trim(coalesce(bnkname, '')) <> ''")
            ->selectRaw('trim(bnkname) as bank')
            ->distinct()
            ->orderBy('bank')
            ->pluck('bank')
            ->map(fn ($bank) => (string) $bank);

        return $codes
            ->unique(fn (string $bank) => mb_strtolower($bank))
            ->sort(fn (string $left, string $right) => strcasecmp($left, $right))
            ->values();
    }

    public function scopeBank($query, string $bank)
    {
        $bank = trim($bank);

        if ($bank === '') {
            return $query;
        }

        return $query->whereRaw('lower(trim(bnkname)) = ?', [mb_strtolower($bank)]);
    }
}
