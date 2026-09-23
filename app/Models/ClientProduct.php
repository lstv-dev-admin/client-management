<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProduct extends Model
{
    protected $primaryKey = 'recid';

    protected $fillable = [
        'comcode',
        'prdname',
        'prdnoli',
        'prdvers',
    ];

    public static function detailFields(): array
    {
        return [
            'prdname' => 'Product Name',
            'prdnoli' => 'Product Number of License',
            'prdvers' => 'Product Version',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'comcode', 'comcode');
    }
}
