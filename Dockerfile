FROM php:7-apache
ARG eccube_version=3.0.14
RUN apt-get update && apt-get install -y git unzip libzip-dev libpq-dev
RUN a2enmod rewrite
RUN docker-php-ext-install -j$(nproc) zip pgsql pcntl posix pdo_pgsql
RUN cd /usr/src && \
    curl -L -o /usr/src/ec-cube-${eccube_version}.tar.gz https://github.com/EC-CUBE/ec-cube/archive/${eccube_version}.tar.gz && \
    tar xf /usr/src/ec-cube-${eccube_version}.tar.gz && \
    mv ec-cube-${eccube_version} ec-cube && \
    rm -rf /var/www/html && ln -s /usr/src/ec-cube/html /var/www/html
VOLUME ["/root/.composer"]
WORKDIR /usr/src/ec-cube
RUN curl -sS https://getcomposer.org/installer | php && ./composer.phar require --no-interaction payjp/payjp-php && ./composer.phar install --dev --no-interaction
ADD . /usr/src/ec-cube/app/Plugin/PayJp
RUN chown -R www-data:www-data /usr/src/ec-cube
ADD entrypoint.sh .
ENTRYPOINT ["/usr/src/ec-cube/entrypoint.sh"]
CMD ["apache2-foreground"]
