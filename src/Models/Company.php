<?php

namespace Zenapply\Shared\Models;

use App\Exceptions\Model\DuplicateModelException;
use App\Events\CompanyWasCreated;
use Exception;
use Hash;
use Request;

class Company extends Base
{
    protected $guarded = array("id");

    protected $table = 'companies';
    public $with = [];//array('culture','signupQuestions');
    public $withs = array('nodeSettings','images');

    /**
     * {@inheritdoc}
     * 
     * @var array
     */
    protected $rules = [
        'name'            => 'required',
        'domain'          => 'required',
        'primary'         => 'color',
        'secondary'       => 'color',
        'color_header'    => 'color',
        'color_me'        => 'color',
        'color_work'      => 'color',
        'color_school'    => 'color',
        'color_fun'       => 'color',
        'color_questions' => 'color'
    ];

    /**
     * Returns an array of validation rules
     * @return array An Array of validation rules
     */
    protected function getValidationRules(){
        $rules = $this->rules;

        if(isset($rules['domain']) && !empty($this->id)){
            $rules['domain'] .= "|unique:companies,domain,".$this->id.',id';
        }

        return $rules;
    }

    protected static function boot()
    {
        parent::boot();

        self::saving(function($company){
            //Lowercase the domain
            $company->domain = strtolower($company->domain);

            //Check if protected
            if(in_array($company->domain,['public','www','all','admin','apply','demo','stage1','stage2'])){
                throw new Exception($company->domain." is a protected domain. Please use another.");
            }
            
            //Check for duplicates
            $builder = self::where('domain',$company->domain);
            if(!empty($company->id)){
                $builder->where('id','!=',$company->id);
            }
            if($builder->count() > 0){
                throw new DuplicateModelException("Company with that domain already exists!");
            }

            //Run validations
            $company->validate($company);
        });

        self::created(function($company){
            event(new CompanyWasCreated($company));
        });
    }

    /**
     * Return an assoc array containing logo info
     * @return array Logo info
     */
    public function getLogo(){
        $logo = $this->images->last();
        return [
            "path"=>Request::root().$logo->directory."/".$logo->filename,
            "background"=>$this->color_header,
        ];
    }

    /**
     * Checks if this company has a specific product enabled
     * @param  string  $name Name of product
     * @return boolean       
     */
    public function hasProduct($name = null)
    {
        if (is_string($name)) {
            foreach ($this->products as $r) {
                if ($r->name === $name) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks if this company has a specific module enabled
     * @param  string  $name Name of module
     * @return boolean       
     */
    public function hasModule($name = null)
    {
        if (is_string($name)) {
            foreach ($this->modules as $r) {
                if ($r->name === $name) {
                    return true;
                }
            }
        }
        return false;
    }

    /*==============================================
    =            Eloquent Relationships            =
    ==============================================*/
    public function culture()
    {
        return $this->hasOne('App\Culture', 'cid');
    }

    public function products()
    {
        return $this->belongsToMany('App\Product', 'company_products', 'company_id', 'product_id');
    }

    public function modules()
    {
        return $this->belongsToMany('App\Module', 'company_modules', 'company_id', 'module_id');
    }

    public function nodeSettings()
    {
        return $this->hasMany('App\NodeSettings', 'cid');
    }

    public function locations()
    {
        return $this->hasMany('App\Location', 'cid');
    }

    public function signupQuestions()
    {
        return $this->hasMany('App\SignupQuestionSettings', 'cid');
    }

    public function images()
    {
        return $this->belongsToMany('App\Image', 'file_company', 'company_id', 'file_id');
    }

    public function roles()
    {
        return $this->hasMany('App\Role', 'cid');
    }

    public function permissions()
    {
        return $this->hasMany('App\Permission', 'cid');
    }

    public function positions()
    {
        return $this->hasMany('App\Position', 'cid');
    }

    public function flags()
    {
        return $this->hasMany('App\Flag', 'cid');
    }
    /*=====  End of Eloquent Relationships  ======*/    
}
