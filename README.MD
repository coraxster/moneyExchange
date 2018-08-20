# moneyExchange

### install
```bash
docker-compose run workspace sh -c "composer install && php artisan migrate"
```

### run
```bash
docker-compose up -d
```

### seed data(optional)
```bash
docker-compose run workspace php artisan db:seed
```

### refresh db(optional)
```bash
docker-compose run workspace php artisan migrate:refresh
```

### stop
```bash
docker-compose stop
```

### browse
```
http://127.0.0.1:8085/report/{walletId}
```

### test
open Paw.paw in repo

### curl
```
## Add User
curl -X "POST" "http://127.0.0.1:8085/api/user" \
     -H 'Content-Type: application/json; charset=utf-8' \
     -H 'Accept: application/json' \
     -d $'{
  "currency": "USD",
  "country": "country",
  "name": "name",
  "city": "city"
}'

## Add Exchange Rates
curl -X "POST" "http://127.0.0.1:8085/api/rates" \
     -H 'Content-Type: application/json; charset=utf-8' \
     -H 'Accept: application/json' \
     -d $'{
  "date": "2018-01-02",
  "source_currency": "RUB",
  "rate": "1.234567"
}'

## Refill money
curl -X "POST" "http://127.0.0.1:8085/api/wallet/9/refill" \
     -H 'Content-Type: application/json; charset=utf-8' \
     -d $'{
  "amount": 100.01,
  "currency": "USD"
}'

## Transfer money
curl -X "POST" "http://127.0.0.1:8085/api/wallet/9/transfer" \
     -H 'Content-Type: application/json; charset=utf-8' \
     -H 'Accept: application/json' \
     -d $'{
  "amount": 100.13,
  "currency": "RUB",
  "to_wallet_id": "2"
}'
```