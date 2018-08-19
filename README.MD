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

