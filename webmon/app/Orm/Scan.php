<?php

namespace App\Orm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scan extends Model
{
    use SoftDeletes;

    protected $dates = [
        'last_scan',
        'created_at',
        'updated_at'
    ];

    protected $fillable = ['domain_id', 'results', 'scan_type', 'last_scan'];

    protected function domain()
    {
        return $this->belongsTo('App\Orm\Domain');
    }
}