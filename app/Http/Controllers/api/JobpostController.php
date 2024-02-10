<?php
namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper as H;
use App\Models\User;
use App\Models\Profile;
use App\Models\JobPost;
use App\Models\JobOffer;
use App\Models\JobQuestion;
use App\Mail\GeneralEmail;

class JobpostController extends Controller

{
    public function __construct(Request $request)

    {

        $this->request = $request;

    }

    public function JobPostImage($id)

    {

        $data = JobPost::find($id);

        $profile_image = ($data['photo'] == null) ? 'avatar.png' : $data['photo'];

        $path = public_path('assets/jobimages/'.$profile_image);

        $file = File::get($path);

        $type = File::mimeType($path);

        $response = \Response::make($file, 200);

        $response->header("Content-type", $type);

        return $response;

    }

    private function toSendFCM($msg)

    {

        $data = ["title" => 'New Job Post', "body" => $msg, "text" => $msg];

        $h = new H();

        $users = Profile::whereNotNull('fcm_token')->get();

        foreach ($users as $row) {

            $h->sendFCM($row->fcm_token, $data);    

        }

    }

    public function JobPost()

    {

        $inputs = $this->request->all();

        $v = Validator::make($inputs, [

            'what_do_you' => 'required',
            'where_do_you' => 'required',
            'required_Date' => 'required',
            'required_time_range' => 'required',
            'detail' => 'required',
            'budget' => 'required',
            'inperson' => 'required',
            'remotely' => 'required',

        ]);

        if($v->fails()){

            return R::ValidationError($v->errors());

        }

        $photo = '';

        $dt = gettype($inputs['photo']);

        if ($dt != 'string'){

            $photo = $this->UploadJobPost();

        }    

        $jobData = [

            'what_do_you' => $inputs['what_do_you'],
            'where_do_you' => $inputs['where_do_you'],
            'required_Date' => $inputs['required_Date'],
            'required_time_range' => $inputs['required_time_range'],
            'detail' => $inputs['detail'],
            'budget' => $inputs['budget'],
            'inperson' => $inputs['inperson'],
            'remotely' => $inputs['remotely'],
            'posted_by_id' => Auth::id(),
            'lat' => $inputs['lat'],
            'lng' => $inputs['lng'],
            'place_id' => $inputs['place_id'],
            'place_url' => $inputs['place_url'],
            'photo' => $photo

        ];


       DB::beginTransaction();     

       try {

            $post = JobPost::create($jobData);

            DB::commit();

            $user = User::where('id', Auth::id())->with('profile')->first();

            $msg = '<p>

            Your job, <a href="http://jobtasker.au/search-job/'.$this->format_uri($inputs['what_do_you']).'/'.$post->id.'">'.$inputs['what_do_you'].'</a> has been posted successfully and its live, soon you

            will be getting offers.<br>

            If you have any questions, please feel free to <a href="https://jobtasker.au/contact-us">Contact Us</a>.<br>

            Thank you,<br>

            JobTasker

            </p>';

            Mail::to($user->email)->send(new GeneralEmail(['name' =>$inputs['what_do_you'],'to' =>$user->name],' JobTasker', $msg));

            $this->toSendFCM($inputs['what_do_you']);

            return R::Success('Job Posted Successfully', $jobData);

       } catch (Exception $e) {

         DB::rollback();

         return R::SimpleError("Can't save data");  

       }

    }


    public function UploadJobPost()

    {

        $this->request->validate([

            'photo' => 'required',

        ]);

        $fileName = time().'.'.$this->request->photo->extension();  

        $this->request->photo->move(public_path('assets/jobimages/'), $fileName);

        return $fileName;

    }

    public function GetPostList()

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer.OfferBy','totalQuestion.QuestionBy')

        ->where('status','OPEN')

        ->orderBy('id','DESC')

        ->get();

        return R::Success('Job Post List', $data);

    }

    public function GetPost($id)

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer.OfferBy','totalQuestion.QuestionBy')

                ->where('id', $id)

                ->first();

        return R::Success('Job Post', $data);

    }

    public function ReceiverOffer(){
        $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'job_post_id' => 'required',
        //    'assign_to_id' => 'required',
            'amount' => 'required',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $jobData = [
            'job_post_id' => $inputs['job_post_id'],
            'amount' => $inputs['amount'],
         //   'assign_to_id' => $inputs['assign_to_id'],
            'detail' => $inputs['detail'],
            'offer_by_id' => Auth::id()
        ];
       DB::beginTransaction(); 
       try {

            $dt = JobOffer::create($jobData);
            DB::commit();
            $data = JobOffer::where('id', $dt->id)->first();
            $user = User::where('id', Auth::id())->with('profile')->first();
            $assign_to = User::where('id', $data['assign_to_id'])->with('profile')->first();
            $jobPost = JobPost::where('id',$inputs['job_post_id'])->first();

            $postedby = User::where('id', $jobPost->posted_by_id)->with('profile')->first();
            // Job Offer Received By Poster
            $msg = '<p>
            Someone has made an offer for your job <a href="http://jobtasker.au/search-job/'.$this->format_uri($jobPost->what_do_you).'/'.$inputs['job_post_id'].'">'.$jobPost->what_do_you.'</a> See if they are the right person for the job.<br>

            <div><a style="text-decoration: none; padding: 10px 20px; background: #004aad; color: white; text-align: center; text-transform: uppercase;" href="https://jobtasker.au/profile/job-offers/'.$inputs['job_post_id'].'" target="_blank" rel="noopener">Choose Offer</a></div><br>

            If you have any questions, please feel free to <a href="https://jobtasker.au/contact-us">contact us</a><br>
            Thank you, <br>

            JobTasker

            </p>';

            Mail::to($postedby->email)->send(new GeneralEmail(['name' =>' JobTasker - Payment Request - '.$jobPost->what_do_you,'to' =>$postedby->name],' JobTasker', $msg));

            return R::Success('Job Offer Send. Successfully', $data);

       } catch (Exception $e) {
         DB::rollback();
         return R::SimpleError("Can't save data");  
       }
    }

    public function JobPostQuestion($id)

    {

        $data = JobQuestion::with('QuestionBy')

        ->where('job_post_id',$id)

        ->orderBy('id','DESC')

        ->get();

        return R::Success('Job Question', $data);

    }

    public function ReceiverQuestion(){

        $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'job_post_id' => 'required',
            'detail' => 'required',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $jobData = [
            'job_post_id' => $inputs['job_post_id'],
            'detail' => $inputs['detail'],
            'question_by_id' => Auth::id()
        ];

       DB::beginTransaction(); 

       try {
            $dt = JobQuestion::create($jobData);  
            DB::commit();
            $data = JobQuestion::with('QuestionBy')->where('id', $dt->id)->first();
            $user = User::where('id', Auth::id())->with('profile')->first();
            $assign_to = User::where('id', $data['offer_by_id'])->with('profile')->first();
            $jobPost = JobPost::where('id',$inputs['job_post_id'])->first();
            $postedby = User::where('id', $jobPost->posted_by_id)->with('profile')->first();
            // Job Question Sent by Tasker to Poster

            $msg = '<p>

            Someone is interested in your job <a href="http://jobtasker.au/search-job/'.$this->format_uri($jobPost->what_do_you).'/'.$inputs['job_post_id'].'">'.$jobPost->what_do_you.'</a> and is asking you question about it. <br>

            <div><a style="text-decoration: none; padding: 10px 20px; background: #004aad; color: white; text-align: center; text-transform: uppercase;" href="https://jobtasker.au/profile/job-post-question/'.$inputs['job_post_id'].'" target="_blank" rel="noopener">View Questions</a></div><br>

            If you have any questions, please feel free to <a href="https://jobtasker.au/contact-us">contact us</a><br>

            Thank you, <br>

            JobTasker

            </p>';

            Mail::to($postedby->email)->send(new GeneralEmail(['name' =>' JobTasker - Question - '.$jobPost->what_do_you,'to' =>$postedby->name],' JobTasker', $msg));

            return R::Success('Job Question Sent. Successfully', $data);

       } catch (Exception $e) {

         DB::rollback();

         return R::SimpleError("Can't save data");  

       }

    }

    public function ReceiverQuestionReplay(){

        $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'id' => 'required',
            'replay' => 'required',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $jobData = [
            'replay' => $inputs['replay']
        ];
       DB::beginTransaction();  
       try {

            $dt = JobQuestion::where('id',$inputs['id'])->update($jobData);  
            DB::commit();

            $dttt = JobQuestion::where('id',$inputs['id'])->first();

            $data = JobQuestion::with('QuestionBy')
            ->where('job_post_id', $dttt->job_post_id)
            ->orderBy('id','DESC')
            ->get();
            $user = User::where('id', Auth::id())->with('profile')->first();
            $jobPost = JobPost::where('id',$dttt->job_post_id)->first();
            //$assign_to = User::where('id', $data['job_post_id'])->with('profile')->first();
            $questionBy = User::where('id', $dttt->question_by_id)->with('profile')->first();
            // Job Question Reply by Poster to Tasker
            $msg = '<p>

            Someone commented on your offer for <a href="http://jobtasker.au/search-job/'.$this->format_uri($jobPost->what_do_you).'/'.$jobPost->id.'">'.$jobPost->what_do_you.'</a>. <br>

            <div><a style="text-decoration: none; padding: 10px 20px; background: #004aad; color: white; text-align: center; text-transform: uppercase;" href="http://jobtasker.au/search-job/'.$this->format_uri($jobPost->what_do_you).'/'.$jobPost->id.'" target="_blank" rel="noopener">View Comment</a></div>

            If you have any questions, please feel free to <a href="https://jobtasker.au/contact-us">contact us</a><br>

            Thank you, <br>

            JobTasker

            </p>';

            // Mail::to($questionBy->email)->send(new GeneralEmail(['name' =>' JobTasker - Question Reply - ','to' =>$user->name],' JobTasker', $msg));
            Mail::to($questionBy->email)->send(new GeneralEmail(['name' =>' JobTasker - Question Reply - ','to' =>$questionBy->name],' JobTasker', $msg));

            return R::Success('Job Question Send. Successfully', $data);

       } catch (Exception $e) {

         DB::rollback();

         return R::SimpleError("Can't save data");  

       }

    }
    // Filter

    public function GetPostListHeightLow()

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer')

        ->where('status','OPEN')

        ->orderBy('budget','DESC')

        ->get();

        return R::Success('Job Post List', $data);

    }

    public function GetPostListLowHeight()

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer')

        ->where('status','OPEN')

        ->orderBy('budget','ASC')

        ->get();

        return R::Success('Job Post List', $data);

    }

    public function GetPostListDueDateEarly()

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer')

        ->where('status','OPEN')

        ->orderBy('required_date','DESC')

        ->get();

        return R::Success('Job Post List', $data);

    }

    public function GetPostListDueDateLast()

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer')

        ->where('status','OPEN')

        ->orderBy('required_date','ASC')

        ->get();

        return R::Success('Job Post List', $data);

    }

    public function GetPostListOldDest()

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer')

        ->where('status','OPEN')

        ->orderBy('id','DESC')

        ->get();

        return R::Success('Job Post List', $data);

    }

    public function GetPostListCloseToMe()

    {

        $data = JobPost::with('postedBy','timeRange','totalOffer')

        ->where('status','OPEN')

        ->orderBy('id','ASC')

        ->get();

        return R::Success('Job Post List', $data);

    }

    public function GeToBeDone()

    {

        $inputs = $this->request->all();

        if ($inputs['tobedone'] == 'inperson') {

            $data = JobPost::with('postedBy','timeRange','totalOffer')

            ->where('inperson','true')

            ->get();

            return R::Success('Job Post List', $data);

        }

        if ($inputs['tobedone'] == 'remotely') {

            $data = JobPost::with('postedBy','timeRange','totalOffer')

            ->where('remotely','true')

            ->get();

            return R::Success('Job Post List', $data);

        }

        if ($inputs['tobedone'] == 'all') {

            $data = JobPost::with('postedBy','timeRange','totalOffer')

            ->get();

            return R::Success('Job Post List', $data);

        }

    }

    function format_uri( $string, $separator = '-' ){
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = array( '&' => 'and', "'" => '');
        $string = mb_strtolower( trim( $string ), 'UTF-8' );
        $string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
        $string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
        $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);
        return $string;
    }

}



