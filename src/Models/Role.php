<?php

namespace Zenapply\Shared\Models;

use Input;
use Auth;
use App;

class Role extends Base
{
    protected $guarded = array('id');

    public $with = array('permissions','flags','statuses');

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($role){
            $role->permissions()->sync([]);
            $role->flags()->sync([]);
            $role->statuses()->sync([]);
            $role->users()->sync([]);
        });

        static::saving(function($role){
            if(App::runningInConsole() || Auth::user()->hasPermission('Manage_Users')){
                //permissions
                $permissions = Input::get('permissions');
                if (is_array($permissions)) {
                    $a = array();
                    foreach ($permissions as $obj) {
                        array_push($a, $obj['id']);
                    }
                    $role->permissions()->sync($a);
                }

                //statuses
                $statuses = Input::get('statuses');
                if (is_array($statuses)) {
                    $a = array();
                    foreach ($statuses as $obj) {
                        array_push($a, $obj['id']);
                    }
                    $role->statuses()->sync($a);
                }

                //flags
                $flags = Input::get('flags');
                if (is_array($flags)) {
                    $a = array();
                    foreach ($flags as $obj) {
                        array_push($a, $obj['id']);
                    }
                    $role->flags()->sync($a);
                }
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany('App\User','assigned_roles');
    }

    public function permissions()
    {
        return $this->belongsToMany('App\Permission');
    }

    public function flags()
    {
        return $this->belongsToMany('App\Flag');
    }

    public function statuses()
    {
        return $this->belongsToMany('App\Status');
    }
}
