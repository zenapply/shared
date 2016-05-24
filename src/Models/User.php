<?php

namespace Zenapply\Shared\Models;

use App\ApplicationSetting;
use App\Events\UserWasCreated;
use App\Exceptions\Model\DuplicateModelException;
use App\Flag;
use App\Library\Zenapply\Helper;
use App\Status;
use Auth;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Input;

class User extends Base implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    //Traits
    use Authenticatable, Authorizable, CanResetPassword;
    
    protected $guarded = array("id");

    //Included automatically with everything
    public $with = array(
        'images',
        'roles',
        'ratings',

    );

    //Included automatically with APIController only!
    public $withs = array(
        'positions',
        'history',
        'locations',
        'flags',
        'locationRestrictions',
        'positionRestrictions',
        'notes',
        'available',
        'signupQuestions',
        'eeo',
    );

    /**
     * An array of validation messages
     * @var array
     */
    protected $messages = [
        'username.unique' => 'That email address is taken. Please use a different email address',
    ];

    /**
     * For creating a user from the manage page and Indeed
     * @var array
     */
    protected $rules = array(
        'email' => 'required|email',
        'first_name' => 'required|min:1',
        'last_name' => 'required|min:1',
        'username' => 'required|unique:users',
        'password' => 'required|min:4',
    );

    /**
     * For creating a user from the signup page
     * @var array
     */
    protected $rulesStoreSignup = array(
        'email' => 'required|email',
        'first_name' => 'required|min:1',
        'last_name' => 'required|min:1',
        'username' => 'required|unique:users',
        'password' => 'required|min:4',
        'phone' => 'required|min:10',
        'source' => 'required',
        'state' => 'required|min:2',
        'city' => 'required|min:3',
        'zip' => 'required|min:5',
    );

    /**
     * Returns an array of validation rules
     * @return array An Array of validation rules
     */
    protected function getValidationRules(){
        $supported = ["system","add_user","import","indeed"];
        if($this->hasRoles() || in_array($this->creation_source, $supported)){
            $rules = $this->rules;
        } else if($this->creation_source === "signup"){
            $rules = $this->rulesStoreSignup;
        } else {
            throw new Exception("Unknown creation source! {$this->creation_source}");
        }

        if(isset($rules['username']) && !empty($this->id)){
            $rules['username'] .= ",username,".$this->id.',id';
        }

        return $rules;
    }

    /**
     * Validates the Model
     * @param  Model   $model The Model to check
     * @return boolean        Whether or not the input is valid
     */
    public function validate(Model $model){
        $model = $model->withHidden(['password']);
        return parent::validate($model);
    }


    protected static function boot()
    {
        parent::boot();

        self::created(function ($user){
            // Create the "ME" Occurrence.
            $group = OccurrenceGroup::where('group', '=', 'me')->first();
            $user->occurrences()->save(new Occurrence([
                'name' => 'My Introduction',  
                'type' => 'me',  
                'cid' => $user->cid, 
                'occurrence_group_id' => $group->id
            ]));

            event(new UserWasCreated($user));
        });

        self::saving(function ($user) {
            unset($user->password_confirmation);
        
            $builder = self::where('username',$user->username);
            if(!empty($user->id)){
                $builder->where('id','!=',$user->id);
            }
            if($builder->count() > 0){
                throw new DuplicateModelException("Username already exists! Please try using a different email.");
            }
        
            $c = Helper::company();
            $cid = (!empty($c) ? $c->id : $user->cid);
            if(empty($cid)){
                throw new ValidationException("cid cannot be empty!!!");
            }
            $user->first_name = ucwords(strtolower($user->first_name));
            $user->last_name = ucwords(strtolower($user->last_name));
            $user->email = strtolower($user->email);
            $user->username = strtolower($user->email.$cid);
            $user->state = strtoupper($user->state);
            $user->zip = $user->zip;
            $user->phone = Helper::formatPhoneNumber($user->phone);

            $user->city = ucwords(strtolower($user->city));
            $user->address1 = ucwords(strtolower($user->address1));
            $user->address2 = ucwords(strtolower($user->address2));

            //You need to have permission to update the status property
            if (!Auth::check() || !Auth::user()->hasRoles()) {
                unset($user->status);
            }
        });

        self::saved(function($user){
            $user->setHidden(['password']);

            //locations
            $locations = Input::get('locations');
            if (is_array($locations)) {
                $a = array();
                foreach ($locations as $obj) {
                    if (is_array($obj)) {
                        array_push($a, $obj['id']);
                    } else {
                        array_push($a, $obj);
                    }
                }
                $user->locations()->sync($a);
            }

            //positions
            $positions = Input::get('positions');
            if (is_array($positions)) {
                $a = array();
                foreach ($positions as $obj) {
                    if (is_array($obj)) {
                        array_push($a, $obj['id']);
                    } else {
                        array_push($a, $obj);
                    }
                }
                $user->positions()->sync($a);
            }

            if (Auth::check() && Auth::user()->hasPermission("Manage Users")) {

                //locationRestrictions
                $locationRestrictions = Input::get('location_restrictions');
                if (is_array($locationRestrictions)) {
                    $a = array();
                    foreach ($locationRestrictions as $obj) {
                        array_push($a, $obj['id']);
                    }
                    $user->locationRestrictions()->sync($a);
                }

                //posiiton_restrictions
                $positionRestrictions = Input::get('position_restrictions');
                if (is_array($positionRestrictions)) {
                    $a = array();
                    foreach ($positionRestrictions as $obj) {
                        array_push($a, $obj['id']);
                    }
                    $user->positionRestrictions()->sync($a);
                }

                // roles
                $roles = Input::get('roles');
                $cid = Input::get('cid');
                if (is_array($roles)) {
                    $a = array();
                    foreach ($roles as $obj) {
                        if($cid === $obj['cid'] || $obj['cid'] === 0)
                            array_push($a, $obj['id']);
                    }
                    $user->roles()->sync($a);
                }
            }
            if (Auth::check() && Auth::user()->hasRoles()) {
                $flags = Input::get('flags');
                if (is_array($flags)) {
                    $a = array();
                    if (is_array($flags)) {
                        foreach ($flags as $flag) {
                            if (is_array($flag)) {
                                array_push($a, $flag['id']);
                            } else {
                                array_push($a, $flag);
                            }
                        }
                    }
                    $user->flags()->sync($a);
                }
            }
            
        });

        static::deleting(function($user){
            foreach($user->images()->get() as $a){$a->delete();};
            foreach($user->files()->get() as $a){$a->delete();};
            foreach($user->available()->get() as $a){$a->delete();};
            foreach($user->occurrences()->get() as $a){$a->delete();};
        });
    }

    public function calcPercentage(){
        echo "------------------------------------------------------------------".PHP_EOL;
        $settings = $this->getApplicationSettings();
        $count = 0;
        $total = 0;
        foreach ($settings as $s) {
            if(strpos($s,'ns') === 0){
                $ans = $this->verifyNodeSetting($s);
                // echo $s." is a "."nodes_setting: ".$ans[0]."/".$ans[1].PHP_EOL;
            } else if(strpos($s,'q')===0){
                $ans = $this->verifyQuestion($s);
                // echo $s." is a "."question: ".$ans[0]."/".$ans[1].PHP_EOL;
            } else {
                $ans = $this->verifySection($s);
                // echo $s." is a "."section: ".$ans[0]."/".$ans[1].PHP_EOL;
            }
            $count += $ans[0];
            $total += $ans[1];
        }
        if($total!==0){
            $this->percentage=$count/$total;
        } else {
            $this->percentage=0;
        }
        echo "User #".$this->id." is at ".(($this->percentage)*100)."%".PHP_EOL;
    }

    private function verifyNodeSetting($s){
        $total = 0;
        $count = 0;
        $id = intval(str_replace("ns","",$s));
        $result = NodeSettings::find($id);
        if(is_object($result)){
            $results = Node::where('cid',$this->cid)->where('uid',$this->id)->where('name',$result->name)->get();
            foreach ($results as $r) {
                switch($r->type){
                    case "photo":
                    case "document":
                    $count += (count($r->files) > 0 ? 1 : 0);
                    break;
                    case "video":
                    case "question_video":
                    $count += (count($r->videos) > 0 ? 1 : 0);
                    break;
                    case "references":
                    $count += (count($r->references) > 0 ? 1 : 0);
                    break;
                    case "word_cloud":
                    $count += (count($r->words) > 0 ? 1 : 0);
                    break;
                    default:
                    throw new Exception("Unknown type! ".$r->type);
                    break;
                }
                $total += 1;
            }
            // dd($results->toArray());
        }
        return [$count,$total];
    }
    private function verifyQuestion($s){
        $total = 0;
        $count = 0;
        $id = intval(str_replace("q","",$s));
        $result = Question::find($id);
        if(is_object($result)){
            // dd($result->toArray());
            $o = null;
            // echo $result->type.PHP_EOL;
            switch($result->type){
                case "written":
                $o = QuestionText::where('question_id',$result->id)->where('user_id',$this->id)->first();
                if(is_object($o)){
                    $count += (!empty($o->text) ? 1 : 0);
                }
                break;
                case "boolean":
                $o = QuestionBool::where('question_id',$result->id)->where('user_id',$this->id)->first();
                if(is_object($o)){
                    $count += 1;
                }
                break;
                case "video":
                $o = Video::where('question_id',$result->id)->where('user_id',$this->id)->first();
                if(is_object($o)){
                    $count += 1;
                }
                break;
                default:
                throw new Exception("Unknown type! ".$result->type);
                break;
            }
            
            $total += 1;
            // dd($results->toArray());
        }
        return [$count,$total];
    }
    private function verifySection($s){
        $result = Occurrence::where('type',$s)->where('uid',$this->id)->count();
        // echo "Has ".$result." ".$s." occurrences".PHP_EOL;
        $value = ($result > 0 ? 1 : 0);
        return [$value,1];
    }

    private function getApplicationSettings(){
        $settings = [];
        $as = ApplicationSetting::where('cid',$this->cid)
            ->where('position_id',0)
            ->where('status','required')
            ->get();

        foreach ($as as $s) {
            array_push($settings,$s->model_id);
        }
        foreach ($this->positions() as $p) {
            $as = ApplicationSetting::where('cid',$this->cid)
                ->where('position_id',$p->id)
                ->where('status','required')
                ->get();
            foreach ($as as $s) {
                array_push($settings,$s->model_id);
            }
        }
        $settings = array_unique($settings);
        return $settings;
    }

    public function referral_campaign_user(){
        return $this->belongsTo('App\ReferralCampaignUser');
    }

    public function available()
    {
        return $this->hasMany('App\Available');
    }

    public function ratings()
    {
        return $this->hasMany('App\UserRating');
    }

    public function signupQuestions()
    {
        return $this->hasMany('App\SignupQuestion');
    }

    public function history()
    {
        return $this->hasMany('App\History');
    }

    public function adjectives()
    {
        return $this->hasMany('App\Adjective', 'uid');
    }

    public function eeo()
    {
        return $this->hasOne('App\Models\Eeo');
    }

    public function files()
    {
        return $this->hasMany('App\File', 'uid');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role', 'assigned_roles');
    }

    public function occurrences()
    {
        return $this->hasMany('App\Occurrence', 'uid');
    }

    public function images()
    {
        $i = $this->belongsToMany('App\Image', 'file_user', 'user_id', 'file_id');
        $i->orderBy('id', 'desc');
        return $i;
    }

    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    public function locations()
    {
        return $this->belongsToMany('App\Location');
    }

    public function locationRestrictions()
    {
        return $this->belongsToMany('App\LocationRestriction', 'location_restrictions_user');
    }

    public function positionRestrictions()
    {
        return $this->belongsToMany('App\PositionRestriction', 'position_restrictions_user');
    }

    public function positions()
    {
        return $this->belongsToMany('App\Position')->select(['positions.id', 'positions.cid', 'name'])->withPivot('order');
    }

    public function flags()
    {
        return $this->belongsToMany('App\Flag');
    }

    public function getStatusRestrictions()
    {
        $statuses = [];
        Status::where('cid',Helper::company()->id)->get();
        foreach ($this->roles as $r) {
            if($r->name !== 'Zenapply Super Admin'){
                if($r->statuses->count() === 0){
                    // return [];
                }else{
                    foreach ($r->statuses as $s) {
                        if(!array_key_exists($s->name,$statuses)){
                            $statuses[$s->name] = 1;
                        } else {
                            $statuses[$s->name]++;
                        }
                    }
                }
            }
        }
        $ss = [];
        foreach ($statuses as $key => $value) {
            if($value===count($this->roles))
                array_push($ss,$key);
        }
        return $ss;
    }

    public function getFlagRestrictions()
    {
        $flags = [];
        Flag::where('cid',Helper::company()->id)->get();
        foreach ($this->roles as $r) {
            if($r->flags->count() === 0){
                return [];
            }
            else{
                foreach ($r->flags as $s) {
                    array_push($flags,$s->id);
                }
            }
        }
        return array_unique($flags);
    }
    
    public function hasRoles($name = null)
    {
        return $this->hasRole($name);
    }

    public function hasRole($name = null)
    {
        if ($name === null) {
            return ($this->roles->count()>0);
        }
        if (is_string($name)) {
            foreach ($this->roles as $r) {
                if ($r->name === $name) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hasPermission($name = null)
    {
        $name = str_replace(" ", "_", $name);
        if (is_string($name)) {
            foreach ($this->roles as $r) {
                foreach ($r->permissions as $p) {
                    if ($p->name === $name) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('password','confirmation_code','remember_token');

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the e-mail address where password reminders are sent.
     *
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
