# HDR UK Gateway - API (Laravel)

Welcome to the HDR UK Gateway API, a Laravel application that powers the Gateway.

# Getting Started
Follow these steps to install and run the project on your local machine.

## Prerequisites
There are two ways to run our API.
- With Tilt/Helm into a local Kubernetes cluster.
- Or manually, via `php artisan octane:frankenphp --host=0.0.0.0 --port=8100`

Let's take you through both!

# Tilt/Helm & Kubernetes
Start by installing Rancher-Desktop: https://rancherdesktop.io/
-   Ensure that Kubernetes is enabled and running
-   This can be tested by running `kubectl get all` - if running, you'll be presented with a list of running pods, something similar to the following:

```
NAME                               READY   STATUS    RESTARTS        AGE
gateway-web                         1/1     Running   0              83d
gateway-api                         1/1     Running   2              151d
metadata-fed-766d88664b-mpxk2       1/1     Running   1              161d
```

Once confirmed your cluster is up and running, you can now install the remaining tools.

- Helm: https://helm.sh/docs/intro/install/
- Tilt: https://docs.tilt.dev/install.html

With Rancher-desktop, Helm and Tilt installed, you'll need an instance of mysql. Using Helm you can run the following commands:
-   To install the mysql repo: `helm repo add bitnami https://charts.bitnami.com/bitnami`
-   To install mysql container: `helm install mysql bitnami/mysql`
    -   alternatively you can use mariadb: `helm install mariadb oci://registry-1.docker.io/bitnamicharts/mariadb`
-   Provided everything went well, run the following to get the password for your mysql instance: `echo $(kubectl get secret --namespace default mysql -o jsonpath="{.data.mysql-root-password}" | base64 -d)` (make sure to keep a copy of this, and you'll need it for your `.env` file below)

If you don't have a mysql client installed you can use the following to deploy a pod to k8s and act as your mysql client: ` kubectl run mysql-client --rm --tty -i --restart='Never' --image  docker.io/bitnami/mysql:8.0.31-debian-11-r30 --namespace default --env MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD --command -- bash`

-   In order to connect to your mysql pod locally (via any other client), you must ensure you port-forward the mysql port `3306` with the following: `kubectl port-forward <deployed-pod-name> 3306:3306` within a terminal or command window.

If all went well, you're ready to update the `.env` file! Copy the `.env.example` file, to the root of your cloned project and rename it to `.env`. The important parts to update are as follows:

### Database config

```
DB_CONNECTION=mysql # As we named above, when installing with Helm
DB_HOST="mysql" # As we named above, when installing with Helm
DB_PORT=3306
DB_DATABASE="YOUR_DB_NAME" # After creating a database within mysql, enter the name here
DB_USERNAME=YOUR_MYSQL_USERNAME
DB_PASSWORD=YOUR_MYSQL_PASSWORD
```

Once that's complete. You should be able to test the APIs connection to the database, by running: `php artisan migrate` - this will connect to the database, and start creating the base table schema required.

Provided that all completed without error, then its time to update the mail server settings:

```
MAIL_MAILER=smtp
MAIL_HOST=your.smtp.host
MAIL_PORT=25
MAIL_USERNAME=YOUR_SMTP_USERNAME
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### tiltconf.json
If you've opted to use Tilt/Helm, then you'll need to create a `tiltconf.json` file within the root of your cloned project. The file should contain the following:

```
{
    "name": "gateway-api",

    "gatewayWeb2Root": "/path/to/gateway-web/repo",

    "elasticServiceRoot": "/path/to/elastic/repo",
    "traserServiceRoot": "/path/to/traser/repo",
    "tedServiceRoot": "/path/to/ted/repo",
    "fmaServiceRoot": "/path/to/gmi/repo",
    "searchServiceRoot": "/path/to/search-service/repo",
    "qbServiceRoot": "/path/to/qb-service/repo",
    "darasServiceRoot": "/path/to/daras/repo",
    "clamavServiceRoot": "/path/to/clamav/repo",

    "elasticEnabled": true||false,
    "traserEnabled": true||false,
    "tedEnabled": true||false,
    "fmaEnabled": true||false,
    "searchEnabled": true||false,
    "qbEnabled": true||false,
    "darasEnabled": true||false,
    "clamavEnabled": true||false
}
```

The configuration above, can be tweaked to your needs. You can choose to run the components you need and ignore the rest. Provided the `ServiceRoot`'s are representative of your cloned directories, everything should work!

### Running the API.

#### Tilt & Helm

-   If you've opted to use Tilt/Helm, then, within the root of your cloned directory, run the following command:

```
tilt up
```

You'll see that Tilt starts invoking Docker to build an image, and ultimately instructs Helm to deploy the pod/service to your local cluster. If you've configured `web` and any of the other services, then these are built and deployed at the same time!

#### Manual
- If you've opted to run manually, then within the root of your cloned directory, run the following command:

```
php artisan octane:frankenphp --host=0.0.0.0 --port=8100
```
Note: This will run only the API. No other services, nor the web client.

By default, the application, under both forms of running, will be available at: `http://localhost:8100/api/v1/...`

## Available Composer Scripts
- `composer run phpstan` - Runs PHPStan to perform static analysis on code
- `composer run behat` - Runs Behat BDD tests
- `composer run phpcs` - Runs PHP Code Sniffer linting checks
- `composer run pest` - Runs the main Unit/Feature tests
- `composer run lint` - Runs Laravel Pint for PSR specific linting


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

## Contributions

Gateway API follows the existing Laravel coding standards which can be found at https://laravel.com/docs/10.x/contributions

Contributions will be accepted in the form of a raised PR against `dev` branch. Github pipeline includes all linting and testing components to ensure stability.
