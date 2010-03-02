<?php

require dirname(__FILE__)          . '/lib/lime/lib/lime.php';
require dirname(dirname(__FILE__)) . '/MemcacheTagging.class.php';

$lime  = new lime_test(null, new lime_output_color());

////////////////////////////////////////////////////
// TESTS
////////////////////////////////////////////////////

/*
 * connect to server
 */
$cache = MemcacheTagging::getInstance();

$lime->diag('test instantiate');
$lime->ok($cache, 'connect to memcache server without options');

$options = array('servers' => array(
    array(
        'host' => 'localhost',
        'port' => 11211,
    )
));
$cache = MemcacheTagging::getInstance($options);
$lime->ok($cache, 'connect to memcache server with options');


$options = array('servers' => array(
    array(
        'host' => 'localhost',
        'port' => 11211,
    ),
    array(
        'host' => 'localhost',
        'port' => 11211,
    ),
));
$cache = MemcacheTagging::getInstance($options);
$lime->ok($cache, 'connect to multi memcache servers with options');

/**
 * get()
 */
$cache->flush();
$lime->diag('test get()');

$lime->ok($cache->get('hoge') === null, 'returns "null" if no value');
$lime->ok($cache->get('hoge', 1) === 1, 'returns specific value if default value is set');

$datas = array(
    array(
        'key'   => 'key1',
        'value' => 'value_1',
        'tags'  => array(1, 2, 3),
    ),
    
    array(
        'key'   => 'key2',
        'value' => 'value_2',
        'tags'  => array(2, 3, 4),
    ),
);


////////////////////////////////////////////////////

$cache->flush();

$lime->diag('test set() -> get()');
foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);
    
    $gotValue = $cache->get($key);
    $gotTags  = $cache->getTags($key);
    $lime->ok($value === $gotValue, sprintf('Input Value is "%s", Got Value is "%s"', $value, $gotValue));
    $lime->ok($tags  === $gotTags,  sprintf('Input Tags is "(%s)", Got Tags is "(%s)"', join(',', $tags), join(',', $gotTags)));
}

////////////////////////////////////////////////////

$cache->flush();

$lime->diag('test set() -> delete() -> get()');

foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);
    
    $gotValue = $cache->get($key);
    $gotTags  = $cache->getTags($key);
    $lime->ok($value === $gotValue, sprintf('Input Value is "%s", Got Value is "%s"', $value, $gotValue));
    $lime->ok($tags  === $gotTags,  sprintf('Input Tags is "(%s)", Got Tags is "(%s)"', join(',', $tags), join(',', $gotTags)));
    
    $cache->delete($key);
    $gotValue = $cache->get($key);
    $lime->ok($gotValue === null, 'cannot get deleted value');
}

////////////////////////////////////////////////////

$cache->flush();

$lime->diag('test set() -> getByTag()');

foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);
}

$values = $cache->getByTag(1);
$diff1  = array_diff($values, array('value_1'));
$diff2  = array_diff(array('value_1'), $values);
$lime->ok(empty($diff1) && empty($diff2), sprintf('values for "%s" is "(%s)"', 1, join(',', $values)));

$values = $cache->getByTag(2);
$diff1   = array_diff($values, array('value_1', 'value_2'));
$diff2   = array_diff(array('value_1', 'value_2'), $values);
$lime->ok(empty($diff1) && empty($diff2), sprintf('values for "%s" is "(%s)"', 2, join(',', $values)));

////////////////////////////////////////////////////

$cache->flush();

$lime->diag('test set() -> getTags()');
foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);

    $gotTags = $cache->getTags($key);
    
    $diff1 = array_diff($gotTags, $tags);
    $diff2 = array_diff($tags, $gotTags);
    $lime->ok(empty($diff1) && empty($diff2), sprintf('tags for "%s" is "(%s)"', $key, join(',', $tags)));
}

////////////////////////////////////////////////////

$cache->flush();

$lime->diag('test set() -> delete() -> getByTag()');
foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);
}

$cache->delete('key1');

$values = $cache->getByTag('1');
$lime->ok(empty($values), 'values for tag "1" is "emptry"');

$values = $cache->getByTag('2');
$diff1  = array_diff($values, array('value_2'));
$diff2  = array_diff(array('value_2'), $values);
$lime->ok(empty($diff1) && empty($diff2), 'values for tag "2" is "(value_2)"');

$values = $cache->getByTag('3');
$diff1  = array_diff($values, array('value_2'));
$diff2  = array_diff(array('value_2'), $values);
$lime->ok(empty($diff1) && empty($diff2), 'values for tag "3" is "(value_2)"');

$values = $cache->getByTag('4');
$diff1  = array_diff($values, array('value_2'));
$diff2  = array_diff(array('value_2'), $values);
$lime->ok(empty($diff1) && empty($diff2), 'values for tag "4" is "(value_2)"');

////////////////////////////////////////////////////

$cache->flush();
$lime->diag('test set() -> deleteByTag() -> get()');
foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);
}

$cache->deleteByTag('1');
$v1 = $cache->get('key1');
$v2 = $cache->get('key2');
$lime->ok(is_null($v1), '"key1" is deleted');
$lime->ok(!is_null($v2), '"key2" is not deleted');

$cache->flush();
foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);
}

$cache->deleteByTag('2');
$v1 = $cache->get('key1');
$v2 = $cache->get('key2');
$lime->ok(is_null($v1), '"key1" is deleted');
$lime->ok(is_null($v2), '"key2" is deleted');
