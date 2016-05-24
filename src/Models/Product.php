<?php

namespace Zenapply\Shared\Models;

class Product extends Base
{
    protected $guarded = ['id'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * {@inheritdoc}
     * 
     * @var array
     */
    protected $rules = [
        'name'  => 'required|unique:products|string|min:3',
    ];
    
    /*==============================================
    =            Eloquent Relationships            =
    ==============================================*/
    
    public function companies()
    {
        return $this->belongsToMany('App\Company', 'company_products', 'product_id', 'company_id');
    }

    public function modules()
    {
        return $this->belongsToMany('App\Module', 'product_modules', 'product_id', 'module_id');
    }
    
    /*=====  End of Eloquent Relationships  ======*/    
}
