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
    protected $touches = ['location'];

    protected $fillable = [
        'id',
        'name',
        'surname',
        'alive',
        'age',
        'en',
        'de',
        'nl',
        'traits',
        'location_id',
        'residence_id',
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

    public function residence()
    {
        return $this->belongsTo(Location::class, 'residence_id');
    }

    public function parents()
    {
        return $this->belongsToMany(
            Character::class,
            'children',
            '_to',
            '_from',
            '_id',
            '_id',
        );
    }

    public function children()
    {
        return $this->belongsToMany(
            Character::class,
            'children',
            '_from',
            '_to',
            '_id',
            '_id',
        )->using('Tests\Setup\Models\Child')
            ->withPivot([
                            'created_by',
                            'updated_by',
                        ]);
    }

    public function captured()
    {
        return $this->morphMany(Location::class, 'capturable');
    }

    public function conquered()
    {
        return $this->morphOne(Location::class, 'capturable');
    }

    /**
     * Get all of the tags for the post.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
