
FROM php:8.3-apache

# Instala dependências e habilita módulos do Apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Configura o diretório público do projeto
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Permite que o Apache leia o .htaccess da pasta public
RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/public-override.conf \
    && a2enconf public-override

# Diretório de trabalho
WORKDIR /var/www/html

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do projeto para a imagem
COPY . .

# Instala as dependências PHP
RUN composer install --no-interaction --prefer-dist \
    && mkdir -p storage uploads \
    && chown -R www-data:www-data storage uploads


COPY docker/re.source-alias.conf /etc/apache2/conf-available/re.source-alias.conf

RUN a2enconf re.source-alias

EXPOSE 80

