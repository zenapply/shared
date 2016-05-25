<?php

namespace Zenapply\Shared\Models;

use Zenapply\Shared\Models\Company;

class File extends Base
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
    protected $table = 'files';

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($file){
            $file->users()->sync([]);
            $file->companies()->sync([]);
            $file->nodes()->sync([]);
            $file->questions()->sync([]);
        });
    }

    public function users()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\User', 'file_user', 'file_id', 'user_id');
    }

    public function companies()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\Company', 'file_company', 'file_id', 'company_id');
    }
}
