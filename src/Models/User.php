<?php

namespace Zenapply\Shared\Models;

use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Base implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    //Traits
    use Authenticatable, Authorizable, CanResetPassword;
    
    protected $guarded = array("id");

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
}
