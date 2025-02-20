FROM php:8.2-fpm

RUN apt-get update && apt-get upgrade -y
RUN apt-get install -y  \
    software-properties-common \
    jpegoptim \
    imagemagick \
    gnupg \
    g++ \
    gcc \
    make \
    procps \
    openssl \
    git \
    unzip \
    zlib1g-dev \
    libpng-dev \
    libfreetype6-dev \
    libmagickwand-dev \
    libmagickcore-dev \
    libzip-dev \
    libxslt1-dev \
    libonig-dev \
    wget


RUN docker-php-ext-configure gd --with-freetype
RUN pecl install imagick \
	&& docker-php-ext-enable imagick
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    xsl \
    gd \
    intl \
    opcache \
    exif \
    mbstring
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Add the bcmath extension
RUN docker-php-ext-install bcmath

#install Redis
RUN pecl install redis \
        && docker-php-ext-enable redis.so

RUN wget https://getcomposer.org/download/2.7.7/composer.phar && mv composer.phar /usr/local/bin/composer && chmod a+x /usr/local/bin/composer
RUN mkdir -p /root/.composer

WORKDIR /var/www