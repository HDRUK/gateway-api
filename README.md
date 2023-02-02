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
 - To install mysql container: `helm install <container name> bitnami/mysql`
 - Provided everything went well, run the following to get the password for your mysql instance: `echo $(kubectl get secret --namespace default mysql -o jsonpath="{.data.mysql-root-password}" | base64 -d)` (make sure to keep a copy of this, and you'll need it for your `.env` file below)

 If you don't have a mysql client installed you can use the following to deploy a pod to k8s and act as your mysql client: ` kubectl run mysql-client --rm --tty -i --restart='Never' --image  docker.io/bitnami/mysql:8.0.31-debian-11-r30 --namespace default --env MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD --command -- bash`

 - In order to connect to your mysql pod locally (via any other client), you must ensure you port-forward the mysql port `3306` with the following: `kubectl port-forward <deployed-pod-name> 3306:3306`

## Configuring
Copy `.env.example` to `.env` and fill in the required parameters. Namely the database connectivity credentials and host address
 - When port-forwarding mysql port to your localhost (as above), you can connect to mysql locally with either `127.0.0.1` or `localhost`. Generally speaking your mysql instance will be available on `mysql.default.svc.cluster.local`

## Building
From the root of the cloned directory run `docker-compose up` with the optional `-d` argument to run in detached mode. This will run both the API and the Laravel queue monitor.

## Migrations
When needing to update your database schema, you can use the following artisan functions:
`artisan migrate` to migrate/update your local and, `artisan migrate:rollback` to rollback the last migration run.

If running in a docker container, you'll need to remotely interact with artisan with: `docker exec php artisan migrate/migrate:rollback`.

## Contribution
Gateway-api-2 follows the existing Laravel coding standards which can be found at https://laravel.com/docs/9.x/contributions