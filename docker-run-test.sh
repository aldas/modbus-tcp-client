#!/usr/bin/env bash

PHP_VERSION=${1:-7.1}
PHPUNIT_OPTS=${*:2}

# if ARCH env variable is not set then use 64bit as default
if [[ -z "${ARCH}" ]]; then
  ARCH=64bit
fi

if [[ "$ARCH" == "64bit" ]]; then
   IMAGE=php
elif [[ "$ARCH" == "32bit" ]]; then
   IMAGE=i386/php
else
   echo Unknown arch value: ${ARCH}
   exit 1
fi

echo Using ${ARCH} PHP-${PHP_VERSION}
docker run -i -v "${PWD}:/code" -w /code/ ${IMAGE}:${PHP_VERSION}-cli-alpine php /code/vendor/bin/phpunit ${PHPUNIT_OPTS}