# PHP 7.1.33-cli image is Debian buster; apt uses archive.debian.org buster suites with
# debian-archive-keyring copied from bookworm (buster archive signatures), not Stretch.
FROM debian:bookworm-slim@sha256:7c7b2c966bc9ee8cedfeef67e0e279108992c77681fa595db4a9d65c06ccc587 AS debian-keyring
RUN apt-get update && apt-get install -y --no-install-recommends debian-archive-keyring \
    && rm -rf /var/lib/apt/lists/*

FROM php:7.1.33-cli@sha256:e0e6ced092bbf073e4f3e48545a8e1a2592217b3346ef0cb8b18ecacf8ae1522

COPY --from=debian-keyring /usr/share/keyrings/debian-archive-keyring.gpg /usr/share/keyrings/debian-archive-keyring.gpg

RUN set -eux; \
    printf '%s\n' \
      'deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] http://archive.debian.org/debian buster main' \
      'deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] http://archive.debian.org/debian-security buster/updates main' \
      > /etc/apt/sources.list; \
    apt-get -o Acquire::Check-Valid-Until=false update; \
    apt-get -o Acquire::Check-Valid-Until=false install -y --no-install-recommends \
      $PHPIZE_DEPS \
      default-libmysqlclient-dev; \
    docker-php-ext-install pdo_mysql; \
    docker-php-ext-enable pdo_mysql; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /src
