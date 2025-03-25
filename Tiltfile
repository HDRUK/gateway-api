## Gateway API Tiltfile
##
## Loki Sinclair <loki.sinclair@hdruk.ac.uk>
##

# Load Extensions
load("ext://uibutton", "cmd_button", "location", "text_input")

# Configure extra UI elements
cmd_button(
    name="gateway-api-config-clear",
    text="Config clear",
    resource="gateway-api",
    argv=["php", "artisan", "config:clear"],
    icon_name="refresh",
)

cmd_button(
    name="gateway-api-run-tests",
    text="Run Pest",
    resource="gateway-api",
    argv=["kubectl", "exec", "-it", "gateway-api", "--", "composer", "run", "pest"],
    icon_name="search_off",
)

cmd_button(
    name="gateway-api-migrate",
    text="Migrate DB",
    resource="gateway-api",
    argv=[
        "kubectl",
        "exec",
        "-it",
        "gateway-api",
        "--",
        "php",
        "artisan",
        "migrate:fresh",
    ],
    # argv=['php', 'artisan', 'migrate:fresh'],
    icon_name="database",
)

cmd_button(
    name="gateway-api-seed",
    text="DB Seed",
    resource="gateway-api",
    argv=["kubectl", "exec", "-it", "gateway-api", "--", "php", "artisan", "db:seed"],
    icon_name="table",
)

# Load in any locally set config
cfg = read_json("tiltconf.json")

include(cfg.get("gatewayWeb2Root") + "/Tiltfile")

# Load our service layer for deployment - if enabled
if cfg.get("dtaWebEnabled"):
    include(cfg.get("dtaWebRoot") + "/Tiltfile")
if cfg.get("traserEnabled"):
    include(cfg.get("traserServiceRoot") + "/Tiltfile")

if cfg.get("tedEnabled"):
    include(cfg.get("tedServiceRoot") + "/Tiltfile")

if cfg.get("gmiEnabled"):
    include(cfg.get("gmiServiceRoot") + "/Tiltfile")

if cfg.get("searchEnabled"):
    include(cfg.get("searchServiceRoot") + "/Tiltfile")

if cfg.get("elasticEnabled"):
    include(cfg.get("elasticServiceRoot") + "/Tiltfile")

if cfg.get("qbEnabled"):
    include(cfg.get("qbServiceRoot") + "/Tiltfile")

if cfg.get("darasEnabled"):
    include(cfg.get("darasServiceRoot") + "/Tiltfile")

if cfg.get('clamavEnabled'):
    include(cfg.get('clamavServiceRoot') + '/Tiltfile')

## Implements a watcher for local file changes to automatically
## fix linting issues in real-time, locally.
local_resource('linting', cmd='composer run lint', deps=['./'])


docker_build(
    ref="hdruk/" + cfg.get("name"),
    context=".",
    build_args={
        "TRASER_ENABLED": "1" if cfg.get("traserEnabled") else "0",
        "TED_ENABLED": "1" if cfg.get("tedEnabled") else "0",
        "GMI_ENABLED": "1" if cfg.get("gmiEnabled") else "0",
        "SEARCH_ENABLED": "1" if cfg.get("searchEnabled") else "0",
    },
    live_update=[
        sync(".", "/var/www"),
        run("composer install", trigger="./composer.lock"),
        run("php artisan route:clear"),
        run("php artisan cache:clear"),
        run("php artisan config:clear", trigger="./.env"),
        run("php artisan l5-swagger:generate"),
    ],
)

k8s_yaml("chart/" + cfg.get("name") + "/deployment.yaml")
k8s_yaml("chart/" + cfg.get("name") + "/service.yaml")
k8s_resource(cfg.get("name"), port_forwards=8000, labels=["Service"])
