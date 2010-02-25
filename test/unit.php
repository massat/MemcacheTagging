<?php

require dirname(__FILE__)          . '/lib/lime/lib/lime.php';
require dirname(dirname(__FILE__)) . '/MemcacheTagging.class.php';

$lime  = new lime_test(null, new lime_output_color());
$cache = MemcacheTagging::getInstance();

////////////////////////////////////////////////////

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

$lime->diag('test set() -> getByTags()');

foreach($datas as $data) {
    $key   = $data['key'];
    $value = $data['value'];
    $tags  = $data['tags'];
    
    $cache->set($key, $value, $tags);
}

$values = $cache->getByTag(1);
$diff   = array_diff($values, array('value_1'));
$lime->ok(empty($diff), sprintf('values for "%s" is "(%s)"', 1, join(',', $values)));

$values = $cache->getByTag(2);
$diff   = array_diff($values, array('value_1', 'value_2'));
$lime->ok(empty($diff), sprintf('values for "%s" is "(%s)"', 2, join(',', $values)));