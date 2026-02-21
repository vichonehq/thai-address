<?php

namespace Kingw1\ThaiAddress\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'label',
        'address',
        'subdistrict',
        'district',
        'province',
        'postal_code',
        'contact_phone'
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}
