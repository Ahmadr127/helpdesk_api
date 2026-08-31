<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_number',
        'category_id',
        'category',
        'department_id',
        'department',
        'building_id',
        'building',
        'location_id',
        'location',
        'description',
        'priority',
        'status',
        'opened_at',
        'admin_responses',
        'user_replies',
        'rejection_count',
        'user_confirmation',
        'user_confirmed_at',
        'last_rejection_at',
        'in_progress_at',
        'closed_at'
    ];

    protected $dates = [
        'opened_at',
        'in_progress_at',
        'closed_at',
        'created_at',
        'updated_at',
        'user_confirmed_at'
    ];

    protected $casts = [
        'user_replies' => 'array',
        'created_at' => 'datetime',
        'opened_at' => 'datetime',
        'in_progress_at' => 'datetime',
        'closed_at' => 'datetime',
        'user_confirmation' => 'boolean',
        'user_confirmed_at' => 'datetime',
        'last_rejection_at' => 'datetime',
        'admin_responses' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function departmentRelation()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    
    public function buildingRelation()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }
    
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
    
    public function locationRelation()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }
    
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function photos()
    {
        return $this->hasMany(TicketPhoto::class);
    }

    public function initialPhoto()
    {
        return $this->hasOne(TicketPhoto::class)->where('type', 'initial');
    }

    public function adminResponsePhotos()
    {
        return $this->hasMany(TicketPhoto::class)->where('type', 'admin_response');
    }

    public function getUserResponsePhotosAttribute()
    {
        return $this->photos()->whereIn('type', ['user_response', 'user_rejection'])->get();
    }
}