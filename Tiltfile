## Gateway API Tiltfile
##
## Loki Sinclair <loki.sinclair@hdruk.ac.uk>
##

# Load in any locally set config
cfg = read_json('tiltconf.json')

include(cfg.get('gatewayWeb2Root') + '/Tiltfile')

docker_build(
    ref='hdruk/' + cfg.get('name'),
    context='.',
    live_update=[
        sync('.', '/var/www'),
        run('composer install', trigger='./composer.lock'),
        run('php artisan route:clear'),
        run('php artisan cache:clear'),
        run('php artisan config:clear'),
        run('php artisan config:cache')
    ]
)

k8s_yaml('chart/' + cfg.get('name') + '/' + cfg.get('name') + '.yaml')
k8s_yaml('chart/' + cfg.get('name') + '/deployment.yaml')
k8s_yaml('chart/' + cfg.get('name') + '/service.yaml')
k8s_resource(
   cfg.get('name') + ':pod',
   port_forwards=8000
)