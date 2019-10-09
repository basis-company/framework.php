#!/bin/bash
./composer.phar dump-autoload -o
vendor/bin/console module.bootstrap

if [[ ! -z "${BASIS_JOB}" ]]; then
  while true; do
    vendor/bin/console module.runner
  done
else
  echo "ServerName $SERVICE_NAME" > /etc/apache2/conf-enabled/server-name.conf
  apache2-foreground
fi