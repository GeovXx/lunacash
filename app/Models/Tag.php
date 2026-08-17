<?php

namespace App\Models;

class Tag extends UserOwnedModel
{
    protected $table = 'tags';

    protected $fillable = ['name', 'color', 'metadata'];
}
