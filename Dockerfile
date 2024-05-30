FROM ubuntu:jammy

ENV DEBIAN_FRONTEND=noninteractive


RUN apt-get update -y \
    && apt-get upgrade -y \
    && apt-get install -y software-properties-common

RUN add-apt-repository -y ppa:ondrej/php \
	&& apt-get update -y 

RUN apt-get install -y \
    php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-curl \
    php8.3-xsl \
    php8.3-gd \
    php8.3-common \
    php8.3-xml \
    php8.3-zip \
    php8.3-soap \
    php8.3-bcmath \
    php8.3-mbstring \
    php8.3-gettext \
    composer 

RUN apt install -y libvips42

# debugging attempts for my local apple silicon machine, didn't work
# ENV VIPSHOME=/usr/lib/aarch64-linux-gnu

# Assume your application files are in the same directory as the Dockerfile
# Copy your application files into the /var/www/html directory in the container
COPY . /var/www/html

# Set the working directory to where you've copied your application files
WORKDIR /var/www/html

RUN composer install --prefer-dist --no-scripts --no-dev --no-autoloader && \
    rm -rf /root/.composer

# Dump autoload
RUN composer dump-autoload --no-scripts --no-dev --optimize

# Start PHP's built-in server and point it to the public directory
# Bind it to port 8080
CMD ["php", "-d", "zend.max_allowed_stack_size=-1", "-S", "0.0.0.0:8080", "-t", "public"]
