<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meetup extends Model
{
    protected $fillable = [
        'title', 'time', 'description'
    ];

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

}
