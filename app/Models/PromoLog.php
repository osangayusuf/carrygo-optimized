<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['msisdn', 'segment_type', 'last_sent_at', 'send_count'])]
#[Table('carrygo_promo_log')]
class PromoLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
        ];
    }
}
