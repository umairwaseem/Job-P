<?php



namespace App\Http\Controllers\api;



use App\Http\Controllers\Controller;

use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;

use Illuminate\Http\Request;

use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper as H;

use App\Models\Skill;
use App\Models\PostAdCategory;
use App\Models\ProfileCategory;
use App\Models\Profile;
use App\Models\User;

use App\Mail\GeneralEmail;

use Carbon\Carbon;
use Twilio\Rest\Client; 



class PublicListController extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function SkillList()
    {
        $data = Skill::get();

        return R::Success('Job Skills', $data);
    }
   
    public function CategoryList()
    {
        $data = PostAdCategory::get();

        return R::Success('Job Skills', $data);
    }

    public function TaskerByCategory()
    {
        $data = User::with('Profile.Categories.category','Profile.MyJobs')
        ->where('user_type','tasker')
        ->get();

        return R::Success('Tasker By Category', $data);
    }

    public function PopularCategory()
    {
        $profile_cat = ProfileCategory::select('post_ad_category_id')->get();

        $data = PostAdCategory::whereIn('id', $profile_cat)->get();

        return R::Success('Popular Category', $data);
    }

    public function SendMessage()
    {
        $h = new H();

        $resp = $h->sendSMS("+923419226064","hello 11");

        return R::Success('Tasker By Category', $resp);
    }

    public function SendContactEmail()
    {
        //$this->request

        $msg = '<p>Name : '.$this->request->name.'</p>';
        $msg .= '<p>Email : '.$this->request->email.'</p>';
        $msg .= '<p>Message : '.$this->request->msg.'</p>';
        Mail::to('support@jobtasker.au')->send(new GeneralEmail(['name' =>' Contact from - '.$this->request->name,'to' => 'Support'],' JobTasker', $msg));

        return R::Success('Job Assigned Successfully', '');

    }

}

