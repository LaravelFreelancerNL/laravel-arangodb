<?php

namespace Tests\Setup\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class Tag extends Model
{
    use SoftDeletes;

    protected $table = 'tags';

    protected $fillable = [
        '_key',
        'en',
        'de',
    ];

    /**
     * Get all of the characters that are assigned this tag.
     */
    public function characters()
    {
        return $this->morphedByMany(Character::class, 'taggable');
    }

    /**
     * Get all of the locations that are assigned this tag.
     */
    public function locations()
    {
        return $this->morphedByMany(Location::class, 'taggable');
    }


    /**
     * Get all of the locations that are assigned this tag.
     */
    public function houses()
    {
        return $this->morphedByMany(House::class, 'taggable');
    }
}
