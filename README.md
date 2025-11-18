# Redis Cache

## Install
```bash
composer require llegaz/redis-cache
composer install
```

## PSR-6
PSR-6 implementation is my implementation of `Caching Interface` from the PHP FIG [www.php-fig.org/psr/psr-6](https://www.php-fig.org/psr/psr-6)
It is far from perfect and as of now (first versions ofg this implementation) you should now than pool expiration are available but by pool's fields expiration are not !
I will try to test and implement a pool key expiration using [Valkey.io](https://valkey.io) in a near future but my first draft is: if you expire a pool key it will expire your entire pool SO BE EXTA CAUTIAUX WITH THAT !


### Caution
**if you expire a pool key it will expire your entire pool SO BE EXTA CAUTIAUX WITH THAT !**

### Basic usage
```php
$cache = new LLegaz\Cache\RedisEnhancedCache();
$cart = new \LLegaz\Cache\Pool\CacheEntryPool($cache);
$user = new \LLegaz\Cache\Pool\CacheEntryPool($cache, 'lolo');

$id = $user->getItem('id');
if ($id->isHit()) {
    $item = $cart->getItem('banana:' . $id->get());
    $item->set('mixed value');
    $cart->save($item);
} else {
    $id->set('the lolo id');
    $user->save($id);
}
```

### Batch
```php
foreach ($cart => $item) {
    $cart->saveDeferred($item); // items are commited on pool object destruct
}
```


## PSR-16
My `Simple Cache` implementation PHP FIG PSR-16 [www.php-fig.org/psr/psr-16](https://www.php-fig.org/psr/psr-16)
```php
    $cache = new LLegaz\Cache\RedisCache();
    $cache->selectDatabase(3); // switch database
    $cache->set('key', 'mixed value, could be an object or an array');
    $cache->get('key'));


    $cache->setMultiple(['key1' => [], 'key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'], 90); // 1m30s expiration on each key
    if ($cache->has('key1') && $cache->has('key4')) {
        $array = $cache->getMultiple(['key1', 'key1', 'key4']));
        var_dump(count($array)); // 2
    }
```

## Configuration
```php
$cache = new LLegaz\Cache\RedisCache('localhost', 6379, null, 'tcp', 0, false);
```
is equivalent to
```php
$cache = new LLegaz\Cache\RedisCache();
```
or
```php
$cache = new LLegaz\Cache\RedisCache('127.0.0.1');
```

### Persistent connection
```php
$cache = new LLegaz\Cache\RedisCache('localhost', 6379, null, 'tcp', 0, true);
```


## Contributing
You're welcome to propose things. I am open to criticism as long as it remains benevolent.


Stay tuned, by following me on github, for new features using [predis](https://github.com/predis/predis) and [PHP Redis](https://github.com/phpredis/phpredis/).

---
@see you space cowboy
---