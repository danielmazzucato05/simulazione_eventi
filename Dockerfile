# Usa l'immagine ufficiale di PHP con Apache
FROM php:8.2-apache

# Aggiorna i pacchetti e installa le estensioni di PostgreSQL necessarie per PDO
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Abilita il modulo rewrite di Apache (utile per le SPA)
RUN a2enmod rewrite

# Copia tutto il codice nella root di Apache
COPY . /var/www/html/

# Render imposta dinamicamente la porta tramite la variabile d'ambiente PORT.
# Modifichiamo la configurazione di default di Apache per ascoltare su questa porta.
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Assicuriamoci che i permessi siano corretti per l'utente www-data
RUN chown -R www-data:www-data /var/www/html

# Avvia Apache in foreground (il default per l'immagine php:apache)
