<?php

namespace App\Orm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 */
class Notification extends Model
{

    protected $fillable = ['user_id', 'scan_type', 'domain_id'];
}