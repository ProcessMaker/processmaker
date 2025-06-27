# ProcessMaker JSON Optimization

## Overview

This document describes the implementation of optimized JSON decoding in ProcessMaker using the [SIMDJSON PHP extension](https://github.com/ondrejsimek/php-simdjson) to improve performance in JSON processing.

> **Note:** SIMDJSON is used **only for `json_decode`**. Encoding (`json_encode`) remains native, as SIMDJSON does not support it.

## Benefits

* **2–4x faster** JSON decoding compared to native `json_decode`
* **Reduced CPU usage** when parsing large or frequent JSON payloads
* **Improved API performance**
* **Graceful fallback** to native decoding if SIMDJSON is not available

---

## Installation

#### 1. Install SIMDJSON PHP Extension

**Ubuntu/Debian:**

```bash
sudo apt-get update
sudo apt-get install libsimdjson-dev php-pear php-dev build-essential
sudo pecl install simdjson
```

**macOS:**

```bash
brew install simdjson
pecl install simdjson
```

#### 2. Configure PHP

Edit your `php.ini` and ensure the following line is present:

```ini
extension=simdjson.so
```

Restart your web server or PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

#### 3. Configure Laravel

Enable JSON optimization in your `.env` file:

```env
JSON_OPTIMIZATION=true
```

---

## Usage

Instead of using `json_decode()` directly, use the wrapper:

```php
use ProcessMaker\Support\JsonOptimizer;

$data = JsonOptimizer::decode($json);
```

Or, if you’ve added a global helper:

```php
$data = json_optimize_decode($json);
```

The optimizer will automatically use `simdjson` if available, and fall back to `json_decode` otherwise.

---

## Testing

### Verify Extension Loaded

```bash
php -m | grep simdjson
```

Expected output:

```
simdjson
```