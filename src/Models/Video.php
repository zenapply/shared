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
        'video_id' => 'string',
        'video_service' => 'required_with:video_id|in:youtube,vimeo,viddler',
        'video_url' => 'required_with:video_id|url',
    ];

    /*==============================================
    =            Eloquent Relationships            =
    ==============================================*/
    
    /*=====  End of Eloquent Relationships  ======*/
}
