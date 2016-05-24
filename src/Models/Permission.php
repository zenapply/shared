<?php

namespace Zenapply\Shared\Models;

class Permission extends Base
{
    protected $guarded = array('id');

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
    protected $table = 'permissions';

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($permission){
            $permission->roles()->sync([]);
        });
    }

    public function roles()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\Role');
    }
}
