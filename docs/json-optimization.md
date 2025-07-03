# ProcessMaker JSON Optimization

## awesome/simdjson_plus
**https://github.com/awesomized/simdjson-plus-php-ext**

Blazing-fast JSON encoding and decoding for PHP, powered by the simdjson project.
This is a fork of JakubOnderka/simdjson_php (which is a fork of crazyxman/simdjson_php)
Since the simdjson PECL extension seems to be unmaintained, or at least slow to accept PRs for improvements, we packaged this up under a new name (simdjson_plus) to avoid naming conflicts and published it on Packagist (instead of PECL) for easier installation.
It's a drop-in replacement for the PECL extension, with additional features from JakubOnderka, such as accelerated JSON encoding (not just decoding) and optimizations.

## Installation

#### 1. Install simdjson-plus-php-ext

**Ubuntu/Debian:**

```bash
sudo apt-get update
sudo apt-get install php-pear php-dev build-essential
```

**macOS, Ubuntu/Debian:**

```bash
git clone https://github.com/ColinHoford/simdjson-plus-php-ext.git
cd simdjson-plus-php-ext
phpize
./configure
make
sudo make install
```

#### 2. Configure PHP

Edit your `php.ini` and ensure the following line is present:

```ini
extension=simdjson_plus.so
```

**Verify Extension Loaded**

```bash
php -m | grep simdjson
```

Expected output:

```
simdjson_plus
```

**Restart your web server and PHP-FPM:**

**Ubuntu/Debian:**
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

**macOS:**
```bash
brew services stop php@8.3
brew services start php@8.3
brew services info php@8.3

brew services stop nginx
brew services start nginx
brew services info nginx
```

#### 3. Configure Laravel

Enable JSON optimization in your `.env` file:

```env
JSON_OPTIMIZATION_ENCODE=true
JSON_OPTIMIZATION_DECODE=true
```

---

## Usage

Instead of using `json_*()` directly, use the wrapper:

```php
use ProcessMaker\Support\JsonOptimizer;

$data = JsonOptimizer::decode($json);
$json = JsonOptimizer::encode($data);
```

Or, if you’ve added a global helper:

```php
$data = json_optimize_decode($json);
$json = json_optimize_encode($data);
```

The optimizer will automatically use `simdjson_plus` if available, and fall back to `json_decode or json_encode` otherwise.
