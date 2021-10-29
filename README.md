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
### With `PHP` and `Symfony` installed 
Request a new game ID.
```bash
php bin/console play --gameServer=<SEVER URL> --newGame
```

With this game ID you can calculate next best move for a player. If `noSubmit` option was provided then client won't send player's next move to API.
```bash
php bin/console play --gameServer=<SERVER URL> --gameId=<GAME ID> --playerId=<PLAYER ID> --noSubmit
```

Without `noSubmit` option player move will be `PUT` to the API.
```bash
php bin/console play --gameServer=<SERVER URL> --gameId=<GAME ID> --playerId=<PLAYER ID> 
```

### Running in `Docker`


## Client exit code is winner player id (on error it is `-1` or `255`)
Check it after running the client
```bash
echo $?
```

