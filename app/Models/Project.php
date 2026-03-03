<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Project extends Model
{
     use SoftDeletes;

    protected $fillable = [
        'title',
        'category_id',
        'thumbnail_image',
        'main_heading',
        'description',
        'project_image',
        'slug',
    ];

    public function images()
    {
        return $this->hasMany(ProjectImage::class);
    }

    public function features()
    {
        return $this->hasMany(ProjectFeature::class);
    }

    public function category()
{
    return $this->belongsTo(Category::class);
}

public function actions()
{
    return $this->hasMany(ProjectUserAction::class);
}




public function likes()
{
    return $this->hasMany(ProjectUserAction::class)
                ->where('is_liked', true);
}

public function interested()
{
    return $this->hasMany(ProjectUserAction::class)
                ->where('is_interested', true);
}


protected $appends = [
    'thumbnail_image_url',
    'project_image_url'
];

public function getThumbnailImageUrlAttribute()
{
    return $this->thumbnail_image
        ? asset('storage/' . $this->thumbnail_image)
        : null;
}

public function getProjectImageUrlAttribute()
{
    return $this->project_image
        ? asset('storage/' . $this->project_image)
        : null;
}
}
