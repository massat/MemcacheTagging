--TEST--
is_deeply method that fails
--FILE--
<?php
require_once(dirname(__FILE__).'/setup.php');
$t->is_deeply(array(1, 2, array(1 => 'foo', 'a' => '4')), array(1, 2, array(1 => 'bar', 'a' => '4')));
$t->is_deeply(array(1, 2, 3), array());
$t->is_deeply(array(), array(1, 2, 3));
?>
--EXPECTF--
not ok 1
#     Failed test (%s/is_deeply_fails.php at line 3)
#            got: array (  0 => 1,  1 => 2,  2 =>   array (    1 => 'foo',    'a' => '4',  ),)
#       expected: array (  0 => 1,  1 => 2,  2 =>   array (    1 => 'bar',    'a' => '4',  ),)
not ok 2
#     Failed test (%s/is_deeply_fails.php at line 4)
#            got: array (  0 => 1,  1 => 2,  2 => 3,)
#       expected: array ()
not ok 3
#     Failed test (%s/is_deeply_fails.php at line 5)
#            got: array ()
#       expected: array (  0 => 1,  1 => 2,  2 => 3,)
1..3
# Looks like you failed 3 tests of 3.
