<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'session_id',
    'msisdn',
    'pcode',
    'session_date',
    'status',
    'session_duration_millis',
    'subid',
    'amount',
    'expirydate',
    'doiflag',
    'channel',
])]
#[Table('carrygo_session_log')]
class SessionLog extends Model
{
    protected $primaryKey = 'tid';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'session_date' => 'datetime',
        ];
    }
}
