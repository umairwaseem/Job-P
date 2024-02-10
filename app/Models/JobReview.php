<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobReview extends Model
{
    use HasFactory;
    protected $table = "job_reviews";

    public function review(){
        return $this->BelongsTo('App\Models\JobPost' , 'job_id' , 'id');
    }

    public function reviewPost(){
        return $this->BelongsTo('App\Models\JobPost' , 'job_id' , 'id');
    }
}
