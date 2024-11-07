# HDR UK - Gateway 2.0 API

### TODO - Flesh this out further as we continue

## Prerequisites

Rancher-Desktop: https://rancherdesktop.io/

-   Ensure that Kubernetes is enabled and running
-   Can be tested by running `kubectl get all` - if running, you'll be presented with a list
    of running pods

Helm: https://helm.sh/docs/intro/install/

With Rancher-desktop and Helm installed, you'll need an instance of mysql. Using Helm you can run the following commands:

-   To install the mysql repo: `helm repo add bitnami https://charts.bitnami.com/bitnami`
-   To install mysql container: `helm install mysql bitnami/mysql`
    -   alternatively you can use mariadb: `helm install mariadb oci://registry-1.docker.io/bitnamicharts/mariadb`
-   Provided everything went well, run the following to get the password for your mysql instance: `echo $(kubectl get secret --namespace default mysql -o jsonpath="{.data.mysql-root-password}" | base64 -d)` (make sure to keep a copy of this, and you'll need it for your `.env` file below)

If you don't have a mysql client installed you can use the following to deploy a pod to k8s and act as your mysql client: ` kubectl run mysql-client --rm --tty -i --restart='Never' --image  docker.io/bitnami/mysql:8.0.31-debian-11-r30 --namespace default --env MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD --command -- bash`

-   In order to connect to your mysql pod locally (via any other client), you must ensure you port-forward the mysql port `3306` with the following: `kubectl port-forward <deployed-pod-name> 3306:3306`

## Configuring

Copy `.env.example` to `.env` and fill in the required parameters. Namely the database connectivity credentials and host address

-   Your .env should look something like this

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=gateway
DB_USERNAME=<username>
DB_PASSWORD=<password>
```

-   When port-forwarding mysql port to your localhost (as above), you can connect to mysql locally with either `127.0.0.1` or `localhost`. Generally speaking your mysql instance will be available on `mysql.default.svc.cluster.local`

Create a new (gitignore'd) `tiltconf.json` following the same format as below:

```
{
    "gatewayWeb2Root": "/Users/lokisinclair/Development/HDRUK/gateway-web-2",
    "name": "gateway-api"
}
```

-   gatewayWeb2Root - should be the path to you gateway web cloned repo.
-   name - should be `gateway-api`. This is used by Tilt/Helm and K8s to name your final built and deployed image.

## Building

From the root of the cloned gateway-api directory run `tilt up`. This will run both the API.

## Migration and Databse Seeding
### Migration

When needing to update your database schema, you can use the following artisan functions:
`artisan migrate` to migrate/update your local and, `artisan migrate:rollback` to rollback the last migration run.

If running in a docker container, you'll need to remotely interact with artisan with: `docker exec php artisan migrate/migrate:rollback`.

### Seeding

The database can be seeded for local testing using:
```
kubectl exec -it $(kubectl get pods | awk '/gateway-api/ {print $1}') -- php artisan db:seed
```


To migrate and seed at the same time you can do:
```
kubectl exec -it $(kubectl get pods | awk '/gateway-api/ {print $1}') -- php artisan migrate:fresh --seed 
```

### Production

For production we don't want to seed all the fake data used for development, you can instead just migrate and run the baseline database seeder like so:
```
kubectl exec -it $(kubectl get pods | awk '/gateway-api/ {print $1}') -- php artisan migrate:fresh --seed --seeder=BaseDatabaseSeeder 
```

Follows steps in the mongo-migration-suite to migrate real data.



## Contribution

Gateway-api-2 follows the existing Laravel coding standards which can be found at https://laravel.com/docs/9.x/contributions

## PhpStan

command

```
php -d memory_limit=4G vendor/bin/phpstan analyse
```

or

```
.vendor/bin/phpstan analyse
```

## Temporary endpoints

To test the validity of your build and deployment you can call the following API to determine if everything is running as
expected

`http://localhost:8000/api/status`

If everything went to plan, you'll see a response of

```json
{
    "message": "OK"
}
```

### Register User - simple registration

```
POST /api/v1/register HTTP/1.1
Accept: application/json
Content-Type: application/json

{
    "name": "name",
    "email": "email@email.com",
    "password": "password"
}
```

### Authorization - simple registration

```
POST /api/v1/auth HTTP/1.1
Accept: application/json
Content-Type: application/json

{
    "email": "email@email.com",
    "password": "password"
}
```

### Using Jwt - for testing purposes

```
GET /api/v1/test HTTP/1.1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6MzAwNiIsInN1YiI6IiIsImF1ZCI6IkxhcmF2ZWwiLCJpYXQiOiIxNjc3ODUzMTI2IiwibmJmIjoiMTY3Nzg1MzEyNiIsImV4cCI6IjE2Nzg0NTc5MjYiLCJqdGkiOiI3RGdLZ2ZWdm9jRE5yVnp4WEpQUjNma0p1cGxOMFBzSVE0UEluampiQUVIUkRtRkx4d3BIYnZDYkxQUHRpc2lNIiwidXNlciI6eyJwcm92aWRlcmlkIjoiMTAwNzQyMTI4ODY0Mzk1MjQ5NzkxIiwibmFtZSI6IkRhbiBOaXRhIiwiZmlyc3RuYW1lIjoiRGFuIiwibGFzdG5hbWUiOiJOaXRhIiwiZW1haWwiOiJkYW4ubml0YS5oZHJ1a0BnbWFpbC5jb20iLCJwcm92aWRlciI6Imdvb2dsZSIsInVwZGF0ZWRfYXQiOiIyMDIzLTAzLTAzVDE0OjE4OjQ2LjAwMDAwMFoiLCJjcmVhdGVkX2F0IjoiMjAyMy0wMy0wM1QxNDoxODo0Ni4wMDAwMDBaIiwiaWQiOjF9fQ.2qPNxpVRxykJJ3XdKAIedE-xlEQPTc03QSrUHzEaufc
Cookie: sessionId=s%3AHfJi8k9SMiyRHI5YXO_hkBeeyIG7AoW6.jMV%2BFjZKCOGPCW8IqZc%2F%2B8nvYULQ7Wq%2BbmusGfhKzcE
```

### Login - google / linkedin / azure auth

```
GET [host]/api/v1/auth/{provider}
```

where provider can take values like:

```
google or linkedin or azure
```

### Tests

Commands:

```
vendor/bin/phpunit
```

or

```
vendor/bin/phpunit --testdox
```

or

```
php -d memory_limit=2048M ./vendor/bin/phpunit
```

or for one single file test

```
vendor/bin/phpunit --testdox --filter ActivityLogTest
```

or

```
php -d memory_limit=2048M ./vendor/bin/phpunit --testdox --filter ActivityLogTest
```

### Laravel Status Code

```
https://gist.github.com/jeffochoa/a162fc4381d69a2d862dafa61cda0798
```



## OMOP database

### Setup
Point your `.env` to the OMOP db
```
DB_OMOP_CONNECTION=mysql
DB_OMOP_HOST=
DB_OMOP_PORT=
DB_OMOP_DATABASE=
DB_OMOP_USERNAME=
DB_OMOP_PASSWORD=
```

### Create Tables
Create the tables from the omop folder
```
kubectl exec -it $(kubectl get pods | awk '/gateway-api/ {print $1}') --  php artisan migrate --database=localomop --path ./database/migrations/omop

```

### Seed tables
```
 kubectl exec -it $(kubectl get pods | awk '/gateway-api/ {print $1}') --  php artisan db:seed --class=Database\\Seeders\\Omop\\ConceptSeeder  --database=localomop
```

### Authentication problems 
If the auth process does work and times out, try:
```
php artisan key:generate
php artisan config:cache
```

### Laravel Queues
Laravel queues, now uses Horizon (https://laravel.com/docs/10.x/horizon) - which makes use of Redis. This means that a redis instance is required within your cluster. To install, run `helm install redis-local oci://registry-1.docker.io/bitnamicharts/redis`, once provisioned, you can use the following to retrieve the configured password: `export REDIS_PASSWORD=$(kubectl get secret --namespace default redis-local -o jsonpath="{.data.redis-password}" | base64 -d)`

Then update your local .env file to reflect the use of redis within the local cluster as follows:

```
REDIS_HOST="CLUSTER_URL_FROM_INSTALLATION_OUTPUT"
REDIS_PASSWORD=PASSWORD_FROM_ABOVE_COMMAND
REDIS_PORT=6379

# You also need to update your local QUEUE_CONNECTION config to
QUEUE_CONNECTION=redis

```


### Setup email locally

```
helm repo add codecentric https://codecentric.github.io/helm-charts
helm install mailhog codecentric/mailhog
```

- read cluster: similar with `mailhog.default.svc.cluster.local`

start:
port forward Kubernetes mailhog service
```
kubectl port-forward <mailhog-port-name> 1025:1025 8025:8025
```

After running the command, port forwarding will be established, allowing you to access MailHog’s SMTP server and web interface from your local machine.

- To access the SMTP server, you can configure your email client or use a command-line tool like telnet to connect to localhost:1025
- To access the web interface, you can open your web browser and go to `http://localhost:8025`

setup in laravel env
```
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```
