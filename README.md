# Redis Cache
This project is build upon my first redis open PHP project [Redis Adapter](https://packagist.org/packages/llegaz/redis-adapter).
Thanks to it you can use either [Predis](https://github.com/predis/predis) client or native [PHP Redis](https://github.com/phpredis/phpredis/) client in a transparent way.

If PHP redis is installed
```bash
$ apt-get install php8.x-redis
```
These implementations will use it or fallback on Predis client otherwise.

## Install
```bash
composer require llegaz/redis-cache
composer install
```

## PSR-6
PSR-6 implementation is my implementation of `Caching Interface` from the PHP FIG [www.php-fig.org/psr/psr-6](https://www.php-fig.org/psr/psr-6)
It is far from perfect and as of now (first versions of this implementation) you should be aware that pool expiration are available but by pool's fields expiration are not really !
I will try to test and implement a pool key expiration for [Valkey.io](https://valkey.io) in a near future but my first draft is: if you expire a pool key it will expire your entire pool SO BE EXTRA CAUTIOUS WITH THAT !


### Caution
**if you expire a pool key it will expire your entire pool SO BE EXTRA CAUTIOUS WITH THAT !**

### Basic usage
Of course you should do cleaner, proper implementation, the below example is not production ready, it is simplified and given ONLY for the sake of example !
```php
$cache = new LLegaz\Cache\RedisEnhancedCache();
// retrieve user_id as $id
$user = new \LLegaz\Cache\Pool\CacheEntryPool($cache, 'user_data' . $id);
$cart = new \LLegaz\Cache\Pool\CacheEntryPool($cache 'user_cart' . $id);

if ($this->bananaAdded()) {
    $item = $cart->getItem('product:banana');
    $item->set(['count' => 3, 'unit_price' => .33, 'kg_price' => 1.99, 'total_price' => 0]]); // yeah today bananas are free
    $cart->save($item);
    $cartItem = $user->getItem('cart');
    // increment $cartItem here
    $user->save($cartItem);
}
```

### Batch
```php
foreach ($cart as $item) {
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
        var_dump(count($array)); // int(2)
        var_dump($array); // array(2) { ["key1"]=> string(6) "value1" ["key4"]=> string(6) "value4" }
    }
```

## Configuration
```php
$cache = new LLegaz\Cache\RedisCache();
```
is equivalent to
```php
$cache = new LLegaz\Cache\RedisCache('127.0.0.1');
```
or
```php
$cache = new LLegaz\Cache\RedisCache('localhost', 6379, null, 'tcp', 0, false); // the nulled field is the Redis password in clear text (here no pwd)
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