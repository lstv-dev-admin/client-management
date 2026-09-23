<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    protected $primaryKey = 'recid';

    protected $fillable = [
        'comcode',
        'conperson',
        'condesig',
        'contactnum',
        'conemail',
    ];

    public static function detailFields(): array
    {
        return [
            'conperson' => 'Contact Person',
            'condesig' => 'Designation',
            'contactnum' => 'Contact No.',
            'conemail' => 'Email',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'comcode', 'comcode');
    }
}
