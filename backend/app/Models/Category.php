<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            // $model->created_by = Auth::user()->id;
            // $model->updated_by = Auth::user()->id;
        });

        static::updating(function ($model) {
            // $model->updated_by = Auth::user()->id;
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'url', 'parent_id', 'order', 'icon', 'type', 'status', 'created_by', 'updated_by', 'deleted_by'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }
}
