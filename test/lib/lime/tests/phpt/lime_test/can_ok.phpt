--TEST--
can_ok method
--FILE--
<?php
require_once(dirname(__FILE__).'/setup.php');
class Test { function test() {} }
$t->can_ok(new Test(), 'test');
?>
--EXPECT--
ok 1
1..1
# Looks like everything went fine.
