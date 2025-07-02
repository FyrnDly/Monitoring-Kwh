<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mac_address', 'time_stamp',
        'volt', 'ampere',
        'power', 'energy', 'frequency',
        'power_factor', 'temperature', 'humidity',
    ];

    /**
     * Get the device that owns the Log
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }
}
