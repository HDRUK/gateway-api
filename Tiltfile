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
        sync('./app', '/var/www'),
        sync('./database', '/var/www'),
        sync('./config', '/var/www'),
        sync('./public', '/var/www'),
        sync('./tests', '/var/www'),
        run('composer install', trigger='./composer.lock')
    ]
)

k8s_yaml('chart/' + cfg.get('name') + '/' + cfg.get('name') + '.yaml')
k8s_resource(
   cfg.get('name'),
   port_forwards=8000
)