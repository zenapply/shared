<?php

namespace Zenapply\Shared\Models;

class Video extends Base
{
    protected $guarded = array("id");

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'videos';

    /**
     * {@inheritdoc}
     *
     * @var array
     */
    protected $rules = [
        'video_id' => 'required',
        'video_service' => 'required|in:youtube,vimeo,viddler',
        'video_url' => 'required|url',
    ];

    /*==============================================
    =            Eloquent Relationships            =
    ==============================================*/
    
    /*=====  End of Eloquent Relationships  ======*/
}
