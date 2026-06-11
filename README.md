# Slogy - Tamper-Proof Laravel Logger

Slogy is an innovative Laravel package that provides activity logging with **high data integrity** using a cryptographic chain approach (mini blockchain-inspired). It ensures your audit trail remains intact and verifiable, even against privileged insiders with direct database access.

## ✨ Key Features

1. **Cryptographic Chain Logging** - Every log entry is linked to the previous one using SHA-256 hashes, making unauthorized modifications or deletions immediately detectable
2. **Re-Genesis Block** - Safely clean up old logs without breaking data integrity by creating a new genesis block that links to the last deleted log
3. **Tamper-Proof JSON Archives** - Export logs to JSON with a digital seal (HMAC-SHA256)
4. **Offline-to-Online Archive Validator** - Verify uploaded archive files to ensure authenticity
5. **Artisan Commands** - Built-in commands for verification and cleanup

## 📦 Project Structure

- `/packages/slogy` - Main package directory
- `/playground/laravel-slogy-test` - Laravel application for testing and demonstration

## 🚀 Installation

Install the package via Composer:

```bash
composer require sultonisky/slogy
```

After installation, run the migrations:

```bash
php artisan migrate
```

## 🔧 Configuration

Optionally publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=slogy-config
```

## 💻 Artisan Commands

### Verify Log Chain
Verify the integrity of your entire log chain:

```bash
php artisan slogy:verify
```

### Clean Up Old Logs
Clean up logs that exceed the retention period:

```bash
# Clean without archiving
php artisan slogy:clean

# Clean with archiving (saved to storage/app/slogy/archives/)
php artisan slogy:clean --archive

# Override default retention days
php artisan slogy:clean --days=30
```

## 📖 Usage

### Basic Usage

Add the `HasSlogy` trait to your Eloquent models:

```php
use Illuminate\Database\Eloquent\Model;
use Sultonisky\Slogy\Traits\HasSlogy;

class Product extends Model
{
    use HasSlogy;

    // Optional: Ignore specific attributes from logging
    protected $slogyIgnore = ['password', 'remember_token'];

    // Optional: Only log specific attributes
    // protected $slogyLog = ['name', 'price'];
}
```

All `created`, `updated`, `deleted`, `restored`, and `forceDeleted` operations will automatically be logged with cryptographic hashes!

### Custom Description

Customize the log description by adding a `getSlogyDescription` method to your model:

```php
public function getSlogyDescription(string $action): string
{
    return "Product {$this->name} has been {$action}";
}
```

### Custom Label

Set a custom label by adding the `$slogyLabel` property:

```php
public $slogyLabel = 'name';
```

## 🧪 Running Unit Tests

```bash
cd packages/slogy
composer install
vendor/bin/phpunit
```

This project includes **14 tests and 50 assertions** covering all core features!

Made with ❤️ for the global Laravel community

