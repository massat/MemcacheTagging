--TEST--
todo method with test name
--FILE--
<?php
require_once(dirname(__FILE__).'/setup.php');
$t->todo('test name');
?>
--EXPECT--
ok 1 # TODO test name
1..1
# Looks like everything went fine.
