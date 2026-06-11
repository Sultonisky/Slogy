# Slogy - Tamper-Proof Laravel Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sultonisky/slogy.svg?style=flat-square)](https://packagist.org/packages/sultonisky/slogy)
[![Total Downloads](https://img.shields.io/packagist/dt/sultonisky/slogy.svg?style=flat-square)](https://packagist.org/packages/sultonisky/slogy)
[![Tests](https://github.com/YOUR_USERNAME/slogy/actions/workflows/run-tests.yml/badge.svg)](https://github.com/YOUR_USERNAME/slogy/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/packagist/l/sultonisky/slogy.svg?style=flat-square)](https://packagist.org/packages/sultonisky/slogy)

Slogy is an innovative Laravel package that provides activity logging with **high data integrity** using a cryptographic chain approach (mini blockchain-inspired). It ensures your audit trail remains intact and verifiable, even against privileged insiders with direct database access.

## ✨ Key Features

### 1. Cryptographic Chain Logging
- Every new log entry is linked to the previous one using SHA-256 hashes
- Ensures no log can be modified or deleted without detection
- Genesis block to start new chains

### 2. Re-Genesis Block
- Safely clean up old logs without breaking data integrity
- Maintains a link between deleted logs and remaining logs
- Default retention period: 60 days

### 3. Tamper-Proof JSON Archives
- Export logs to JSON format with a digital seal (HMAC-SHA256)
- Uses Laravel's `APP_KEY` as the secret key
- Archives remain verifiable at any time

### 4. Offline-to-Online Archive Validator
- Validates uploaded archive files to ensure authenticity
- Ensures no changes have been made since export

### 5. Artisan Commands
- `slogy:verify`: Verify the integrity of your entire log chain
- `slogy:clean`: Clean up old logs with Re-Genesis Block support

## 📦 Installation

Install the package via Composer:

```bash
composer require sultonisky/slogy
```

After installation, run the migrations:

```bash
php artisan migrate
```

Optionally publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=slogy-config
```

## 🚀 Usage

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

## 🔧 Configuration

The configuration file is located at `config/slogy.php`:

```php
return [
    'events' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
    ],

    'ignored_attributes' => [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'deleted_at',
    ],

    'user_model' => 'App\Models\User',

    'log_retention_days' => 60,
];
```

## 💻 Artisan Commands

### 1. Verify Log Chain
Verify the integrity of your entire log chain:

```bash
php artisan slogy:verify
```

Success output:
```
Total logs checked: 150
✅ Log chain is valid! All logs are intact and untampered.
```

If any logs have been tampered with:
```
❌ Log chain verification failed!
- Log ID 42: Hash mismatch
  Expected: abc123...
  Actual: xyz789...
```

### 2. Clean Up Old Logs
Clean up logs that exceed the retention period:

```bash
# Clean without archiving
php artisan slogy:clean

# Clean with archiving (saved to storage/app/slogy/archives/)
php artisan slogy:clean --archive

# Override default retention days
php artisan slogy:clean --days=30
```

## 📝 API Reference

### ActivityLog Model

#### Methods
- `ActivityLog::calculateHash(?string $previousHash, array $data, string $timestamp): string` - Calculate hash for a new log entry
- `ActivityLog::getLastLog(): ?ActivityLog` - Get the most recent log entry
- `ActivityLog::verifyChain(): array` - Verify the entire log chain integrity
- `ActivityLog::clean(array $options = []): int` - Clean up old logs
- `ActivityLog::exportToJson($logs): string` - Export logs to JSON with digital seal
- `ActivityLog::validateArchive(string $filePath): array` - Validate an archive file

### VerifyChain Return
```php
[
    'valid' => true|false,
    'invalid_logs' => [/* details about invalid logs */],
    'total_logs' => 150,
]
```

### ValidateArchive Return
```php
[
    'valid' => true|false,
    'error' => 'Error message (if not valid)',
    'metadata' => [/* archive metadata */],
]
```

## 🧪 Testing

Run the unit tests:

```bash
cd packages/slogy
composer install
vendor/bin/phpunit
```

This project includes **14 tests and 50 assertions** covering all core features!

## 📋 Database Architecture

The `activity_logs` table structure:
- `id`: Primary Key
- `user_id`: Foreign Key to users (nullable)
- `action`: Type of action (created/updated/deleted/restored/forceDeleted)
- `description`: Log description
- `model`: Model class name
- `model_id`: Model ID
- `old_values`: Data before changes (JSON)
- `new_values`: Data after changes (JSON)
- `previous_hash`: Previous log's hash
- `current_hash`: Current log's hash
- `is_genesis`: Whether this is a genesis block (boolean)
- `created_at` & `updated_at`: Timestamps

## 🔒 Security

- Uses SHA-256 for log chain hashing
- Uses HMAC-SHA256 for digital seal for archives
- Never stores secrets in logs
- Uses `hash_equals()` for hash comparison (timing-attack resistant)

## 📄 License

This package is open-source under the **MIT license**.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request or report an issue.

---

Made with ❤️ by M. Sultoni for the global Laravel community

