<?php

namespace App\Models;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;


use Illuminate\Database\Eloquent\Model;



class JobPost extends Model

{

    protected $guarded = [];



    // protected $hidden = [

    //     'id',

    // ];



    public function postedBy()
    {
        return $this->hasOne('App\Models\Profile' , 'id' , 'posted_by_id');
    }

    public function assignBy()
    {
        return $this->hasOne('App\Models\Profile' , 'id' , 'assign_to_id');
    }

    public function transaction()
    {
        return $this->hasOne('App\Models\Transactions' , 'job_posts_id' , 'id');
    }

    public function timeRange()

    {

        return $this->hasOne('App\Models\JobTimeRange' , 'id' , 'required_time_range');

    }



    public function totalOffer()

    {

        return $this->hasMany('App\Models\JobOffer', 'job_post_id','id');

    }



    public function totalQuestion()

    {

        return $this->hasMany('App\Models\JobQuestion', 'job_post_id','id');

    }



    public function winOffer()

    {

        return $this->hasOne('App\Models\JobOffer', 'id','job_offer_id');

    }

    public function winOffers()

    {

        return $this->hasOne('App\Models\JobOffer', 'job_post_id','id')->where('offer_by_id', Auth::id());

    }

    public function review(){
        return $this->BelongsTo('App\Models\JobReview' , 'id' , 'job_id');
    }

    public function tasker_review(){
        return $this->BelongsTo('App\Models\TaskerReview' , 'id' , 'job_id');
    }

}