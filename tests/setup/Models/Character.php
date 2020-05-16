<?php

namespace Tests\Setup\Models;

use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class Character extends Model
{
    protected $table = 'characters';

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches  = ['location'];

    protected $fillable = [
        '_key',
        'name',
        'surname',
        'alive',
        'age',
        'traits',
        'location_key',
    ];


    /**
     * Get the last known residence of the character.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function leads()
    {
        return $this->hasOne(Location::class, 'led_by');
    }


}
