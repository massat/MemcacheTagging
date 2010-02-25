--TEST--
skip method with test name
--FILE--
<?php
require_once(dirname(__FILE__).'/setup.php');
$t->skip('test name', 2);
?>
--EXPECT--
ok 1 # SKIP test name
ok 2 # SKIP test name
1..2
# Looks like everything went fine.
