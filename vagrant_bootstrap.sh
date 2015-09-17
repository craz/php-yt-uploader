#!/usr/bin/env bash

# Install packages
add-apt-repository -y ppa:ondrej/php5-5.6
apt-get update
apt-get install -y apache2 python-software-properties software-properties-common php5 php5-curl

# Prepare symlink
if ! [ -L /var/www/html ]; then
	rm -rf /var/www/html
	ln -fs /vagrant /var/www/html
fi