FROM ubuntu:23.04

ARG DEBIAN_FRONTEND=noninteractive

COPY ./src /usr/src/fenrir-stability/src
COPY ./index.php ./.en[v] ./composer.* /usr/src/fenrir-stability/

WORKDIR /usr/src/fenrir-stability

RUN apt-get update
RUN apt-get install php-cli php-xml composer php-bcmath -y
RUN composer install
RUN composer dump-autoload -o

CMD [ "php", "./index.php" ]
