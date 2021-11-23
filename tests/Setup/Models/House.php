<?php

namespace Tests\Setup\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class House extends Model
{
    protected $table = 'houses';

    protected $fillable = [
        '_key',
        'name',
        'location_id',
    ];

    /**
     * Get the last known residence of the character.
     */
    public function head()
    {
        return $this->belongsTo(Character::class, 'led_by');
    }

    /**
     * Get all of the tags for the post.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
