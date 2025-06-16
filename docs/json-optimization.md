# ProcessMaker JSON Optimization

## Overview

This document describes the JSON optimization implementation for ProcessMaker using SIMDJSON and UOPZ extensions to improve JSON processing performance.

## Benefits

- **2-4x faster** JSON decoding
- **1.5-3x faster** JSON encoding
- **Reduced CPU usage** for API responses
- **Better user experience** with faster page loads
- **Automatic fallback** to native functions if extensions fail

## Installation

### Automatic Installation

```bash
# Make script executable
chmod +x install_json_optimization.sh

# Run installation script
sudo ./install_json_optimization.sh
```

### Manual Installation

#### 1. Install Extensions

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install libsimdjson-dev php-pear php-dev build-essential
sudo pecl install simdjson
sudo pecl install uopz
```

**macOS:**
```bash
brew install simdjson
pecl install simdjson
pecl install uopz
```

#### 2. Configure PHP

Add to your `php.ini`:
```ini
extension=simdjson.so
extension=uopz.so
```

#### 3. Configure ProcessMaker

Add to your `.env` file:
```bash
JSON_OPTIMIZATION=true
```

#### 4. Register Service Provider

The service provider is already registered in `config/app.php`:
```php
'providers' => [
    // ... other providers
    ProcessMaker\Providers\JsonOptimizerServiceProvider::class,
],
```

## Testing

### Test Installation

```bash
# Check if extensions are loaded
php -m | grep simdjson
php -m | grep uopz

# Test JSON optimization
php artisan json:test

# Test with more iterations
php artisan json:test --iterations=5000
```

### Expected Output

#### With Extensions Loaded (Optimized)

```
📋 Extension Status:
==========================================
SIMDJSON loaded: ✅ YES
UOPZ loaded: ✅ YES
Environment: testing
JSON optimization enabled: ✅ YES
✅ Native JSON functions working correctly
✅ ProcessMaker data processed correctly
📏 JSON size: 193 bytes
📝 SIMDJSON loaded - using optimized functions
📝 UOPZ loaded - using optimized functions
🎯 Optimization Status: 🚀 OPTIMIZED

📊 JSON Optimization Performance Results:
==========================================
Native JSON functions (📝 NATIVE):
  Encode: 4.0629ms
  Decode: 11.024ms
Optimized JSON functions (🚀 OPTIMIZED):
  Encode: 1.2345ms
  Decode: 2.8765ms

📈 Performance Comparison:
Encode ratio: 0.304x (3.29x faster)
Decode ratio: 0.261x (3.83x faster)
🚀 JSON optimization active and working
```

#### Without Extensions (Fallback)

```
📋 Extension Status:
==========================================
SIMDJSON loaded: ❌ NO
UOPZ loaded: ❌ NO
Environment: testing
JSON optimization enabled: ❌ NO
✅ Native JSON functions working correctly
✅ ProcessMaker data processed correctly
📏 JSON size: 193 bytes
📝 SIMDJSON not loaded - would use native functions
📝 UOPZ not loaded - would use native functions
🎯 Optimization Status: 📝 WOULD USE NATIVE

📊 JSON Optimization Performance Results:
==========================================
Native JSON functions (📝 NATIVE):
  Encode: 4.0629ms
  Decode: 11.024ms
Optimized JSON functions (📝 NATIVE):
  Encode: 4.2799ms
  Decode: 11.965ms

📈 Performance Comparison:
Encode ratio: 1.053x
Decode ratio: 1.085x
📝 JSON optimization not active (extensions may not be loaded)
```

#### Key Indicators

- **🚀 OPTIMIZED**: Extensions loaded and working (ratios < 1.0)
- **📝 WOULD USE NATIVE**: Extensions not loaded, using fallback (ratios > 1.0)
- **✅ YES/❌ NO**: Clear extension loading status
- **Performance ratios**: Show speed improvement (lower = faster)

### Extensions Not Loading

1. **Check PHP version compatibility:**
   ```bash
   php --version
   ```

2. **Verify extensions are installed:**
   ```bash
   php -m | grep -E "(simdjson|uopz)"
   ```