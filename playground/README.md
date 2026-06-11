# Slogy Playground

This is a Laravel playground application for testing the `sultonisky/slogy` package.

## 🚀 Setup Instructions

1. **Navigate to the playground directory:**
   ```bash
   cd playground/laravel-slogy-test
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Configure database in `.env`**

6. **Run migrations:**
   ```bash
   php artisan migrate
   ```

7. **Start the server:**
   ```bash
   php artisan serve
   ```

## 📦 Local Package Development

For local package development, edit the playground's `composer.json` and add the local repository:

```json
"repositories": [
    {
        "type": "path",
        "url": "../../packages/slogy"
    }
]
```

Then install the package:
```bash
composer require sultonisky/slogy
```

## 🧪 Testing Features

You can test the package features by:
1. Creating a product via the web interface
2. Updating and deleting a product
3. Running `php artisan slogy:verify` to verify logs
4. Running `php artisan slogy:clean` to clean up old logs

