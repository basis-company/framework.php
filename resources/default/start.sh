#!/bin/bash
if [[ ! -z "${BASIS_JOB}" ]]; then
  ./vendor/bin/console module.bootstrap
  while true; do
    ./vendor/bin/console module.runner
  done
else
  echo "ServerName $SERVICE_NAME" > /etc/apache2/conf-enabled/server-name.conf
  ./vendor/bin/console module.bootstrap
  apache2-foreground
fi