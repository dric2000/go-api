#!/usr/bin/env bash
# Installation initiale d'une instance Oracle Cloud (Ubuntu 24.04, ARM Ampere A1).
# À lancer une seule fois, en tant qu'utilisateur "ubuntu" : bash setup-server.sh
set -euo pipefail

sudo apt update && sudo apt upgrade -y

# PHP 8.3 (version fournie par Ubuntu 24.04) + extensions utilisées par Laravel
sudo apt install -y nginx mysql-server git unzip curl certbot python3-certbot-nginx \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 22 LTS (Next.js 16 demande Node >= 20.9)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Uploads : l'API accepte des fichiers de 4 Mo max
sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 8M/; s/^post_max_size = .*/post_max_size = 10M/' /etc/php/8.3/fpm/php.ini
# PHP-FPM tourne sous "ubuntu" (propriétaire du code) : pas de conflit de droits entre
# git pull / composer et les fichiers écrits par Laravel (logs, uploads, cache)
sudo sed -i 's/^user = www-data/user = ubuntu/; s/^group = www-data/group = ubuntu/' /etc/php/8.3/fpm/pool.d/www.conf
sudo systemctl restart php8.3-fpm

# Pare-feu de l'image Ubuntu d'Oracle : il bloque 80/443 même si la Security List les ouvre
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 443 -j ACCEPT
sudo netfilter-persistent save

sudo mkdir -p /var/www /var/backups/go
sudo chown ubuntu:ubuntu /var/www /var/backups/go

echo "OK. Étape suivante : sudo mysql_secure_installation puis créer la base (voir guide)."
