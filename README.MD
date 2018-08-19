# moneyExchange

### install
```bash
docker-compose run workspace sh -c "composer install && php artisan migrate"
```

### run
```bash
docker-compose up -d
```

### seed(optional)
```bash
docker-compose run workspace php artisan db:seed
```

### stop
```bash
docker-compose stop
```