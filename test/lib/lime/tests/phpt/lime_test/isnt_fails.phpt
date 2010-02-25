--TEST--
isnt method that fails
--FILE--
<?php
require_once(dirname(__FILE__).'/setup.php');
$t->isnt(false, false);
?>
--EXPECTF--
not ok 1
#     Failed test (%s/isnt_fails.php at line 3)
#       false
#           ne
#       false
1..1
# Looks like you failed 1 tests of 1.
