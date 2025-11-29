<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['name','path','type','documentable_type','documentable_id'];

    public function documentable()
    {
        return $this->morphTo();
    }
}
