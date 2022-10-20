<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() { }

    /**
     *
     */
    public function start() {
        //Creating 13 characters unique ID
        $game_token = uniqid();

        //Saving data to a new Object Game
        $game = new Game();
        $game->game_token = $game_token;
        $game->save();

        //Returning JSON response
        return response()->json([
            '_tokenGame' => $game_token
        ], 200);
    }

    /**
     *
     */
    public function move(Request $request) {
        //Definition of validation rules
        //API expects the payload to have the parameters that are specified
        $this->validate($request, [
            '_tokenGame' => 'required',
            'player' => 'required|int',
            'symbol' => 'required|string',
            'move' => 'required'
        ]);

        $game = Game::where('game_token', $request->input('_tokenGame'))->get()->first();

        //Error Exception for invalid token
        if(!$game) {
            return response('Error : invalid game token', 500);
        }

        return response()->json([

        ], 200);
    }

    /**
     *
     */
    public function reset() {

    }
}
