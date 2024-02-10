<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;



class Profile extends Model

{

    protected $guarded = [];



    // protected $hidden = [

    //     'id',

    // ];



    public function User()
    {
        return $this->belongsTo('App\Models\User' , 'id' , 'id');
    }

    public function Categories()
    {
        return $this->hasMany('App\Models\ProfileCategory','user_id','id')->inRandomOrder();
    }

    public function MyJobs()
    {
        return $this->hasMany('App\Models\JobPost','assign_to_id','id');
    }


    // public function Medals()

    // {

    // 	return $this->hasMany('App\Models\Medal');

    // }



    // public function IntakeMeals()

    // {

    // 	return $this->hasMany('App\Models\IntakeMeal','user_id','id');

    // }



    // public function Consultations()

    // {

    // 	return $this->hasMany('App\Models\Consultation','user_id','id');

    // }

}