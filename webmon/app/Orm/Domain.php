<?php

namespace App\Orm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $domain
 */
class Domain extends Model
{
    use SoftDeletes;

    protected $fillable = ['domain'];
}