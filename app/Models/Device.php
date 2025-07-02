<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'mac_address'];

    /**
     * Get all of the logs for the Device
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'device_id', 'id');
    }
}
