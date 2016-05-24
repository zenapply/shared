<?php

namespace Zenapply\Shared\Models;

class Permission extends Base
{
    protected $guarded = array('id');

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($permission){
            $permission->roles()->sync([]);
        });
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }
}
