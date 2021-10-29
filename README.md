# fllr_clnt
## Filler game client for [fllr](https://github.com/hophiphip/fllr)

## NOTE
App will make requests to `/api/game` endpoint.

## Update `.env` file
```bash
cp .env.sample .env
```

## Generate app secret
```bash
php bin/console regenerate-app-secret
```

## Usage
### NOTE
`https://fllr.herokuapp.com` url will be used further in usage examples, but it can be changed (example: `localhost:8080`)  

### With `PHP` and `Symfony` installed 
```bash
composer install && \
	composer update
```

Request a new game ID.
```bash
php bin/console play --gameServer=https://fllr.herokuapp.com --newGame
```

With this game ID you can calculate next best move for a player. If `noSubmit` option was provided then client won't send player's next move to API.
```bash
php bin/console play --gameServer=https://fllr.herokuapp.com --gameId=<GAME ID> --playerId=<PLAYER ID> --noSubmit
```

Without `noSubmit` option player move will be `PUT` to the API.
```bash
php bin/console play --gameServer=https://fllr.herokuapp.com --gameId=<GAME ID> --playerId=<PLAYER ID> 
```

### Running in `Docker`
Build the container
```bash
docker build -t fc .
```
Run it
```bash
docker run fc
```

Request game id
```bash
docker run -e NEW_GAME=true fc
```

Or pass environment variables
```bash
docker run -e GAME_SERVER=<GAME_SERVER> \
           -e GAME_ID=<GAME_ID> \
           -e PLAYER_ID=<PLAYER_ID> fc
```

## Client exit code is winner player id (on error it is `-1` or `255`)
Check it after running the client
```bash
echo $?
```

## Client options 
 - `--newGame` will request new game id from the `API`
 - `--stat` will print total time solution took in seconds
 - `--noSubmt` if set, client won't send `PUT` request with next player move to the `API`



