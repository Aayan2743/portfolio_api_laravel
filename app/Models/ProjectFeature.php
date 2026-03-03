<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFeature extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'image',
        'description'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    return $this->image
        ? asset('storage/' . $this->image)
        : null;
}
}
