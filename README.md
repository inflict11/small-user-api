## Installation

Clone project:

```bash
git clone https://ateshabaev@bitbucket.org/ateshabaev/php-task-symfony-boilerplate.git
```

With docker:

> Make sure you have docker & docker-compose installed (https://docs.docker.com/get-docker/).

```bash
docker-compose up -d
```

Without Docker:

- Install PostgreSQL or other database
- Install PHP and required dependencies for sql, etc (see .docker/php/Dockerfile for list of dependencies)
- Install & configure Nginx or Apache
- Make sure you change environment variables in `.env` file

## Run Application

See application be URL: http://localhost:10000.

If port `10000` doesn't work, check `APP_PORT` variable in `.env` for the correct port.  
