<?php

namespace Zenapply\Shared\Models;

class Module extends Base
{
    protected $guarded = ['id'];

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'shared';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'modules';

    /**
     * {@inheritdoc}
     * 
     * @var array
     */
    protected $rules = [
        'name'  => 'required|unique:modules|string|min:3',
    ];
    
    /*==============================================
    =            Eloquent Relationships            =
    ==============================================*/
    
    public function companies()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\Company', 'company_modules', 'module_id', 'company_id');
    }

    public function products()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\Product', 'product_modules', 'module_id', 'product_id');
    }

    /*=====  End of Eloquent Relationships  ======*/    
}
