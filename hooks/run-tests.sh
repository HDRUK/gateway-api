#!/usr/bin/env bash

kubectl exec -it gateway-api -- composer run phpstan
kubectl exec -it gateway-api -- composer run pest