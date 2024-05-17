FROM composer:2.6.6 as cpr_build

WORKDIR /app

COPY composer.json .
COPY composer.lock .

RUN composer install --prefer-dist --no-ansi --no-interaction --no-progress --no-scripts && \
    composer run post-root-package-install

COPY . .
RUN composer dumpautoload --optimize

FROM alpine:3.19.0

# Install packages and remove default server definition
RUN apk --no-cache add \
  curl \
  nginx \
  supervisor \
  php82 \
  php82-cli \
  php82-common \
  php82-ctype \
  php82-curl \
  php82-dom \
  php82-fpm \
  php82-fileinfo \
  php82-gd \
  php82-iconv \
  php82-intl \
  php82-json \
  php82-mbstring \
  php82-mysqli \
  php82-opcache \
  php82-openssl \
  php82-pecl-redis \
  php82-pdo \
  php82-pdo_mysql \
  php82-pdo_sqlite \
  php82-phar \
  php82-session \
  php82-simplexml \
  php82-xml \
  php82-xmlwriter \
  php82-tokenizer \
  php82-xmlreader \
  php82-zip \
  php82-zlib \
  inotify-tools

# Configure nginx
COPY ./CICD/nginx/nginx.conf /etc/nginx/nginx.conf

# Configure PHP-FPM
COPY ./CICD/php/fpm-pool.conf /etc/php82/php-fpm.d/www.conf
COPY ./CICD/php/php.ini /etc/php82/conf.d/custom.ini

# Configure supervisord
COPY ./CICD/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create symbolic links to redirect Supervisor logs to stdout/stderr
RUN ln -sf /dev/stdout /var/log/supervisor_stdout.log && \
    ln -sf /dev/stderr /var/log/supervisor_stderr.log

# Setup document root
RUN mkdir -p /var/www/html

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /var/www/html && \
  chown -R nobody.nobody /run && \
  chown -R nobody.nobody /var/lib/nginx && \
  chown -R nobody.nobody /var/log/nginx && \
  chown -R nobody.nobody /var/log/php82

# Add application
WORKDIR /var/www/html
COPY --chown=nobody --from=cpr_build /app /var/www/html/

# Expose the port nginx is reachable on
EXPOSE 80

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1/fpm-ping