<?php

namespace Zenapply\Shared\Models;

class Image extends File
{
    public function users()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\User', 'file_user', 'user_id', 'file_id');
    }
    public function companies()
    {
        return $this->belongsToMany('Zenapply\Shared\Models\Company', 'file_company', 'company_id', 'file_id');
    }
}
