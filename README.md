# Tic-tac-toe Rest API
##### PHP backend services for TicTacToe UI

[![Build Status](https://travis-ci.org/joemccann/dillinger.svg?branch=master)](https://travis-ci.org/joemccann/dillinger)

## Setting up the environment
After pulling the repository, make sure you have docker installed on your machine.
Then go to the root directory and start terminal.
Execute:
```bash
docker-compose up --build
```
**This procedure will take care of creating the following containers**:
- php-fpm
- nginx
- mysql

At the end of the building, your application microservices are available at 
```bash
http://localhost:8000/
```
To stop docker containers  go to the root directory and start terminal.
Execute:
```bash
docker-compose down
```

## Services available
###### **GET /start**
This api allows you to create a new game. It returns a **__tokenGame_**  that must be used by the **UI** to play the same game.  

_**curl example**:_
```bash
curl --location --request GET 'http://localhost:8000/start'
```
Response body:
```json
{
    "success": true,
    "_tokenGame": "635295a510c79"
}
```


###### **POST /move**
This api allows you to make a new move on a started game. Make sure that the value of **__tokenGame_** taken from the **/start API**

**Request must have the following parameters:**
- _tokenGame : string - uiniqid
- player : integer, allowed values 1 - 2
- symbol : string, allowed values X - 0
- row : integer, allowed values 0 -1 - 2
- col : integer, allowed values 0 -1 - 2

If even **just one parameter** is missing, or the allowed types are not respected, API will not validate the request and return an error :
```bash
attribute :attribute is required
:attribute must be one of the following types: :values
```

_**curl example**:_
```bash
curl --location --request POST 'http://localhost:8000/move?_tokenGame=635295a510c79&player=1&symbol=X&row=2&col=0'
```
Response body:
```json
{
    "success": true,
    "win": false,
    "end": false,
    "next_player": 2,
    "game_grid": [
        [ "",  "",  ""],
        [ "",  "",  ""],
		[ "X",  "",  ""],
    ],
    "message": "Player 2 makes the next move"
}
```
**Winning response body:**
```json
{
    "success": true,
    "win": true,
    "end": true,
    "player": 2,
    "game_grid": [
        [ "0",  "",  "X"],
        [ "",  "X",  "0"],
		[ "X",  "",  "0"],
    ],
    "message": "Player 2 win the game !"
}
```
**End of game response body:**
```json
{
    "success": true,
    "win": false,
    "end": true,
    "next_player": 2,
    "game_grid": [
        [ "0",  "X",  "0"],
        [ "0",  "X",  "X"],
		[ "X",  "0",  "0"],
    ],
    "message": "The game ended with no winner"
}
```

This service may respond with an **ERROR** in the following cases. 
Below you can find the API response in case of a service error :

- The token is **invalid**
```json
{
    "success": false,
    "error": "Invalid game token"
}
```
- The **game** (represented by a previous valid token) is **over**
```json
{
    "success": false,
    "error": "The game is already over"
}
```
- The move is invalid : **player or symbol is the same as the previous one**
```json
{
    "success": false,
    "error": "The move is up to the other player"
}
```
- The selected box **has already been used** in a previous move
```json
{
    "success": false,
    "error": "Field is already used"
}
```
###### **POST /end**
This service allows you to finish a game earlier.  Make sure that the value of **__tokenGame_** taken from the **/start API**

**Request must have the following parameters:**
- _tokenGame : string - uiniqid

_**curl example**:_
```bash
curl --location --request POST 'http://localhost:8000/end?_tokenGame=635295a510c79'
```

**Response body:**
```json
{
    "success": false,
    "message": "Game successfully ended"
}
```
