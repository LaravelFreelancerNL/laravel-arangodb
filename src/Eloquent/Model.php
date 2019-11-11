<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;

abstract class Model extends IlluminateModel
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = '_key';
}
