--TEST--
is_deeply method
--FILE--
<?php
require_once(dirname(__FILE__).'/setup.php');
$t->is_deeply(array(1, 2, array(1 => 'foo', 'a' => '4')), array(1, 2, array(1 => 'foo', 'a' => '4')));
?>
--EXPECT--
ok 1
1..1
# Looks like everything went fine.
