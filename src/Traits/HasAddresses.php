<?php

namespace Kingw1\ThaiAddress\Traits;

use Kingw1\ThaiAddress\Models\Address;

trait HasAddresses
{
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
