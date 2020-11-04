<?php

namespace Tests\Setup\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class House extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        '_key',
        'name',
        'location_key'
    ];


    /**
     * Current seat of the house.
     */
    public function seat()
    {
        return $this->hasOne(Location::class);
    }

    /**
     * Get the last known residence of the character.
     */
    public function head()
    {
        return $this->belongsTo(Character::class, 'led_by');
    }

    /**
     * @return HasMany
     */
    public function household()
    {
        return $this->hasMany(Character::class, 'residence_key');
    }
}
