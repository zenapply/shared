<?php

namespace Zenapply\Shared\Models;

class Product extends Base
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
        return $this->belongsToMany('Zenapply\Shared\Models\Company', 'company_products', 'product_id', 'company_id');
    }

    public function modules()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\Module', 'product_modules', 'product_id', 'module_id');
    }
    
    /*=====  End of Eloquent Relationships  ======*/    
}
