<?php

namespace Tests\Setup\Models;

use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        '_key',
        'name',
        'coordinate',
        'led_by',
    ];

    /**
     * Get the last known residence of the character.
     */
    public function character()
    {
        return $this->hasOne(Character::class);
    }

    /**
     * Get the last known residence of the character.
     */
    public function leader()
    {
        return $this->belongsTo(Character::class, 'led_by');
    }
}
