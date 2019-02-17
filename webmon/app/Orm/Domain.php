<?php

namespace App\Orm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Tags\HasTags;

/**
 * @property string $domain
 */
class Domain extends Model
{
    use SoftDeletes,HasTags;

    protected $fillable = ['domain'];
    protected $dates = ['updated_at','deleted_at','created_at'];

    public function setDomainAttribute($value)
    {
        $this->attributes['domain'] = strtolower(trim($value));
    }

    public function notifications()
    {
        return $this->hasMany('App\Orm\Notification');
    }
    public function scans()
    {
        return $this->hasMany('App\Orm\Scan');
    }
}