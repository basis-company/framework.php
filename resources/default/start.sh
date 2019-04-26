#!/bin/bash
if [[ ! -z "${BASIS_JOB}" ]]; then
  ./vendor/bin/console module.bootstrap
  while true; do
    ./vendor/bin/console module.runner
  done
else
  apache2-foreground&
 ./vendor/bin/console module.bootstrap
fi