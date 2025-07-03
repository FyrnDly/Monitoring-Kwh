FROM serversideup/php:8.3

USER root
WORKDIR /app

# Install dependencies
RUN apt update && apt install -y \
  nano libicu-dev libfreetype6-dev \
  libjpeg62-turbo-dev zlib1g-dev libpng-dev \
  nodejs npm git unzip curl && \
  docker-php-ext-install intl bcmath && \
  docker-php-ext-configure gd --with-freetype --with-jpeg && \
  docker-php-ext-install -j$(nproc) gd && \
  apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy source code
COPY . .

# Install PHP & JS dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader && \
    npm install && npm run build

# Setup Laravel
RUN php artisan key:generate && \
    php artisan storage:link

# Expose port and serve
EXPOSE 80
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
