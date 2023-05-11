# HDR UK - Gateway 2.0 API

### TODO - Flesh this out further as we continue


## Prerequisites
Docker-Desktop: https://www.docker.com/products/docker-desktop/
 - Ensure that Kubernetes is enabled and running
 - Can be tested by running `kubectl get all` - if running, you'll be presented with a list
    of running pods

Helm: https://helm.sh/docs/intro/install/

With Docker-desktop and Helm installed, you'll need an instance of mysql. Using Helm you can run the following commands:

 - To install the mysql repo: `helm repo add bitnami https://charts.bitnami.com/bitnami`
 - To install mysql container: `helm install mysql bitnami/mysql`
 - Provided everything went well, run the following to get the password for your mysql instance: `echo $(kubectl get secret --namespace default mysql -o jsonpath="{.data.mysql-root-password}" | base64 -d)` (make sure to keep a copy of this, and you'll need it for your `.env` file below)

 If you don't have a mysql client installed you can use the following to deploy a pod to k8s and act as your mysql client: ` kubectl run mysql-client --rm --tty -i --restart='Never' --image  docker.io/bitnami/mysql:8.0.31-debian-11-r30 --namespace default --env MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD --command -- bash`

 - In order to connect to your mysql pod locally (via any other client), you must ensure you port-forward the mysql port `3306` with the following: `kubectl port-forward <deployed-pod-name> 3306:3306`

## Configuring
Copy `.env.example` to `.env` and fill in the required parameters. Namely the database connectivity credentials and host address
 - Your .env should look something like this
 
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=gateway
DB_USERNAME=<username>
DB_PASSWORD=<password>
```

 - When port-forwarding mysql port to your localhost (as above), you can connect to mysql locally with either `127.0.0.1` or `localhost`. Generally speaking your mysql instance will be available on `mysql.default.svc.cluster.local`

Create a new (gitignore'd) `tiltconf.json` following the same format as below:

```
{
    "gatewayWeb2Root": "/Users/lokisinclair/Development/HDRUK/gateway-web-2",
    "name": "gateway-api"
}
```
- gatewayWeb2Root - should be the path to you gateway web cloned repo.
- name - should be `gateway-api`. This is used by Tilt/Helm and K8s to name your final built and deployed image.

## Building
From the root of the cloned gateway-api directory run `tilt up`. This will run both the API.

## Migrations
When needing to update your database schema, you can use the following artisan functions:
`artisan migrate` to migrate/update your local and, `artisan migrate:rollback` to rollback the last migration run.

If running in a docker container, you'll need to remotely interact with artisan with: `docker exec php artisan migrate/migrate:rollback`.

## Contribution
Gateway-api-2 follows the existing Laravel coding standards which can be found at https://laravel.com/docs/9.x/contributions

## PhpStan

command
```
php -d memory_limit=4G vendor/bin/phpstan analyse
```
or 

```
vendor/bin/phpstan analyse
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

or for one single file test class

```
vendor/bin/phpunit --testdox --filter TagTest
```

or for one single file test method

```
vendor/bin/phpunit --testdox --filter test_update_tag_with_success
```

### Laravel Status Code

```
https://gist.github.com/jeffochoa/a162fc4381d69a2d862dafa61cda0798
```
