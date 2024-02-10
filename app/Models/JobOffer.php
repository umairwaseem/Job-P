<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    protected $guarded = [];
    // protected $hidden = [

    //     'id',

    // ];
    public function OfferBy()
    {
        return $this->hasOne('App\Models\Profile' , 'id' , 'offer_by_id')
        ->selectRaw('profiles.*, (SELECT COUNT(id) FROM job_reviews WHERE tasker_id = profiles.id) as review_count,
        (SELECT FORMAT(COALESCE(SUM(review_rating),0) / COUNT(id),1) FROM job_reviews WHERE tasker_id = profiles.id) as total_rating');
    }
}