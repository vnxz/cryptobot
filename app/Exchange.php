<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{

	protected  $primaryKey = 'id';
	
     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'url', 'fee'
    ];
}
