<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'unit_proses_id', 'status'];

    /**
     * Get the unit proses that owns this category
     */
    public function unitProses()
    {
        return $this->belongsTo(UnitProses::class, 'unit_proses_id');
    }

    /**
     * Get the child categories
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all descendants of the category
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the root categories (categories without parents)
     */
    public static function roots()
    {
        return static::whereNull('parent_id');
    }
} 