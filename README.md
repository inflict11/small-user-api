## Installation

Clone project:

```bash
git clone https://ateshabaev@bitbucket.org/ateshabaev/php-task-symfony-boilerplate.git
```

With docker:

> Make sure you have docker & docker-compose installed (https://docs.docker.com/get-docker/).

```bash
docker-compose up -d
docker-compose exec -T php composer install --no-interaction
docker-compose exec -T php php ./bin/console cache:clear --no-warmup
docker-compose exec -T php php ./bin/console cache:warmup
docker-compose exec -T php php ./bin/console doctrine:migrations:migrate --no-interaction
```

This will start all the required services (check docker-compose.yml for the list of services), clear cache & apply
migrations.

Without Docker:

- Install PostgreSQL or other database
- Install PHP and required dependencies for sql, etc (see .docker/php/Dockerfile for list of dependencies)
- Install & configure Nginx or Apache
- Make sure you change environment variables in `.env` file

## Run Application

See application be URL: [http://localhost:10000](http://localhost:10000).

If port `10000` doesn't work, check `APP_PORT` variable in `.env` for the correct port.  

## Register new website and use API
Run command 
```bash
docker-compose exec -T php php bin/console app:register-website "website name" "website url"
```
name and url have 255 symbols limit.

Then you will get apiKey to use with your requests to API via setting it to "Authorization" header.

While creating User, you need to set firstName, lastName, email (they all have 255 symbols limit), 
also you can set parentId, its id of User without parent himself. Other than that is just like in the task.

## Run unit tests
```bash
docker-compose exec -T php php bin/phpunit
```
