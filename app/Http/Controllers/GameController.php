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
     * Create a new Game token
     *
     * @return json $response
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
            'success'       => true,
            '_tokenGame'    => $game_token
        ], 200);
    }

    /**
     * Makes a move on a given game id
     *
     * @param \Illuminate\Http\Request $request
     * @return json $response
     */
    public function move(Request $request) {
        //Validation rules
        //API expects that payload has the parameters that are specified
        $this->validate($request, [
            '_tokenGame' => 'required',
            'player'     => 'required|int|in:1,2',
            'symbol'     => 'required|string|in:X,0',
            'row'        => 'required|int|in:0,1,2',
            'col'        => 'required|int|in:0,1,2'
        ], [
            'required'  => 'The attribute :attribute is required',
            'in'        => 'The :attribute must be one of the following types: :values',
        ]);

        //Check if game token id exists
        $game_exists = Game::where('game_token', $request->input('_tokenGame'))->get()->first();

        //Error invalid token
        if(!$game_exists) {
            return response()->json([
                'success'   => false,
                'error'     => 'Invalid game token'
            ], 500);
        }

        //Request attribute
        $player = $request->input('player');
        $symbol = $request->input('symbol');
        $row = $request->input('row');
        $col = $request->input('col');

        $error = false;

        //Getting Eloquent Object Game
        $game = Game::find($game_exists->id);

        //First move : new game
        if(is_null($game_exists->last_player) && is_null($game_exists->last_symbol) && $game->ended == 0) {
            $new_game = true;
            $actual_grid = Game::getEmptyGrid();
        } else {
            // We are in game
            $new_game = false;

            // Error ended game
            if($game->ended == 1) {
                $error      = true;
                $message    = "The game is already over";
            }

            // Error invalid move : player or symbol is the same as the previous one
            if(!$error && $game->last_player == $player || $game->last_symbol == $symbol) {
                $error      = true;
                $message    = "The move is up to the other player";
            }

            // Getting game grid from DB and convert to array
            $actual_grid = json_decode($game->grid);

            // Error invalid move : field selected is already used
            if(!$error && !empty($actual_grid[$row][$col])) {
                $error      = true;
                $message    = "Field is already used";
            }
        }

        // Managing Errors
        if ($error) {
            return response()->json([
                'success'   => false,
                'error'     => $message
            ], 500);
        }

        // Updating game grid
        $actual_grid[$row][$col] = $symbol;

        $game->grid = json_encode($actual_grid);
        $game->last_symbol = $symbol;
        $game->last_player = $player;
        $game->move_count  = $game->move_count + 1;

        $end         = false;
        $next_player = $player == 1 ? 2 : 1;

        // Check possible winning
        if( !$new_game && $game->move_count >= 5 && $this->checkWin($symbol, $actual_grid)) {
            $end = true;
            $response = [
                'success'       => true,
                'win'           => true,
                'player'        => $player,
                'game_grid'     => $actual_grid,
                'message'       => 'Player '.$player.' win the game !'
            ];
        } else {
            $message = 'Player '.$next_player.' makes the next move';

            //Check if it would be possible to make next move
            if($this->checkEndGame($actual_grid)) {
                $end = true;
                $message = 'The game ended with no winner';
            }

            //Creating response array
            $response = [
                'success'       => true,
                'win'           => false,
                'end'           => $end,
                'next_player'   => $next_player,
                'game_grid'     => $actual_grid,
                'message'       => $message
            ];
        }

        //Setting the end of the game in database
        if($end) $game->ended = 1;

        //Updating table record
        $game->save();

        return response()->json($response, 200);
    }

    /**
     * End a game from a given token
     *
     * @param \Illuminate\Http\Request $request
     * @return json $response
     */
    public function end(Request $request) {
        //Validation rules
        //API expects that payload has the parameters that are specified
        $this->validate($request, [
            '_tokenGame' => 'required',
        ], [
            'required'  => 'The attribute :attribute is required'
        ]);

        //Check if game token id exists
        $game_exists = Game::where('game_token', $request->input('_tokenGame'))->get()->first();

        //Error for invalid token
        if(!$game_exists) {
            return response()->json([
                'success'   => false,
                'error'     => 'Invalid game token'
            ], 500);
        }

        //Getting Eloquent Object Game
        $game = Game::find($game_exists->id);
        $game->ended = 1;
        $game->save();

        return response()->json([
            'success' => true,
            'message' => 'Game successfully ended'
        ], 200);
    }

    /**
     * Check if the current move results in a win
     *
     * @param string $symbol X|0
     * @param array $grid
     * @return bool
     */
    protected function checkWin( string $symbol, array $grid ) {
        //Vertical win
        for($c = 0; $c < 3; $c++) {
            if($grid[0][$c] == $symbol && $grid[1][$c] == $symbol && $grid[2][$c] == $symbol) return true;
        }

        //Horizontal win
        for($r = 0; $r < 3; $r++) {
            if($grid[$r][0] == $symbol && $grid[$r][1] == $symbol && $grid[$r][2] == $symbol) return true;
        }

        //Diagonal win
        if(
            ($grid[0][0] == $symbol && $grid[1][1] == $symbol && $grid[2][2] == $symbol) ||
            ($grid[0][2] == $symbol && $grid[1][1] == $symbol && $grid[2][0] == $symbol)
        ) return true;

        return false;
    }

    /**
     * Check for other moves in the game
     * if the grid is full return true
     *
     * @param array $grid
     * @return bool
     */
    protected function checkEndGame( array $grid ) {
        $count = 0; //Number of not empty grid fields
        $field = 9; //Grid fields

        for($r = 0; $r < 3; $r++) {
            for($c = 0; $c < 3; $c++) {
                if(!empty($grid[$r][$c])) $count++;
            }
        }
        return $count == $field;
    }
}
