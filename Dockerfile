FROM php:8.2-cli

# 1. Install dependencies sistem yang diperlukan
# git, unzip, zip diperlukan oleh Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libsqlite3-dev \
    && docker-php-ext-install zip pdo pdo_sqlite

# 2. Install Composer dari image resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Set working directory
WORKDIR /app

# 4. (Opsional) Entrypoint agar container tetap jalan jika perlu
# CMD ["tail", "-f", "/dev/null"]