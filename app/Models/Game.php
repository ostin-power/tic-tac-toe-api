<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Game extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'games';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'game_token', 'last_user', 'last_symbol', 'grid', 'created_at', 'updated_at'
    ];


    public $grid = [
        ['','',''],
        ['','',''],
        ['','','']
    ];

}
