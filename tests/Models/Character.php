<?php

namespace Tests\Models;

use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class Character extends Model
{
    protected $table = 'characters';

    protected $fillable = [
        'name',
        'surname',
        'alive',
        'age',
        'traits',
    ];


}
