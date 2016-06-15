<?php

namespace Zenapply\Shared\Models;

use Zenapply\Shared\Exceptions\Model\ValidationException;
use Config;
use Log;
use PulkitJalan\Cacheable\Cacheable;
use Validator;
use Illuminate\Database\Eloquent\Model;
use DB;

abstract class Base extends Model
{
    use Cacheable {
        boot as traitboot;
    }

    /**
     * An array of validation rules
     * @var array
     */
    protected $rules = [];

    /**
     * An array of validation messages
     * @var array
     */
    protected $messages = [
        'required_unless' => 'The :attribute field is required',
        'required'        => 'The :attribute field is required',
        'same'            => 'The :attribute and :other must match.',
        'size'            => 'The :attribute must be exactly :size.',
        'between'         => 'The :attribute must be between :min - :max.',
        'in'              => 'The :attribute must be one of the following types: :values',
    ];

    /**
     * Validates the Model
     * @param  Model   $model The Model to check
     * @return boolean        Whether or not the input is valid
     */
    public function validate(Model $model){
        $validator = Validator::make($model->toArray(), $model->getValidationRules(), $model->getValidationMessages());
        if($validator->fails()){
            throw new ValidationException($validator->errors()->first());
        } else {
            return true;
        }
    }

    /**
     * A method best used to register Eloquent events when the model boots up
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // PulkitJalan\Cacheable\Cacheable
        self::traitboot();

        self::saving(function($model){
            if(!$model->validate($model)){
                return false;
            }
        });
    }

    /**
     * Returns an array of validation rules
     * @return array An Array of validation rules
     */
    protected function getValidationRules(){
        return $this->rules;
    }

    /**
     * Returns an array of validation messages
     * @return array An Array of validation messages
     */
    protected function getValidationMessages(){
        return $this->messages;
    }

    /**
     * Scope a query to only include popular users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderRandom($query)
    {
        return $query->orderBy(DB::raw('random()'));
    }

    /**
     * Returns a random model from the database
     * @return Base
     */
    public static function random(){
        return self::orderRandom()->first();
    }
}
