<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    public function fromChat()
    {
        return $this->hasOne('App\Models\Profile' , 'id' , 'from_id');
    }

    public function toChat()
    {
        return $this->hasOne('App\Models\Profile' , 'id' , 'to_id');
    }

    public function jobPost()
    {
        return $this->hasOne('App\Models\JobPost' , 'id' , 'job_post_id');
    }

}