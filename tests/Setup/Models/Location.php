<?php

namespace Tests\Setup\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        '_key',
        'name',
        'coordinate',
        'led_by',
        'capturable_id',
        'capturable_type',
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

    /**
     * @return HasMany
     */
    public function inhabitants()
    {
        return $this->hasMany(Character::class, 'residence_key');
    }

    public function capturable()
    {
        return $this->morphTo();
    }

    /**
     * Get all of the tags for the post.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
