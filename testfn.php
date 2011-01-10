<?php

include_once('./fn.php');

echo "2 * [1, 2, 3, 4] == [2, 4, 6, 8]\n";
print_r(Fn::map(function($x) { return 2 * $x; }, array(1, 2, 3, 4)));

$sum = function () {
   $vals = func_get_args();
   return Fn::foldl(function($a, $b) { return $a + $b; }, 0, $vals);
};
echo "Sum(1, 2, 3, 4, 5) => 15\n";
print_r($sum(1, 2, 3, 4, 5)); echo "\n";

echo "Zip(array(1, 2, 3), array(3, 2, 1), array(1, 2, 3)) == array((1, 3, 1), (2, 2, 2), (3, 1, 3))\n";
print_r(Fn::zip(array(1, 2, 3), array(3, 2, 1), array(1, 2, 3)));
echo "\n";

$rdiv = function() {
   $vals = func_get_args();
   return Fn::foldr(function($a, $b) { echo "$a / $b = " . $a / $b . "\n"; return $a / $b; }, 2, $vals);
};
echo "RDiv(/ 2 array(8, 12, 24, 4)) => 8.0\n";
echo $rdiv(8, 12, 24, 4) . "\n\n";

echo "flip(\$divv)(1, 0) == 0\n";
$fdivv = Fn::flip(function ($x, $y) { return $x / $y; });
echo $fdivv(1, 0) . "\n\n";

$x = Fn::lambda('/2');
echo "4 / 2\n";
echo $x(4) . "\n\n";

$y = Fn::lambda('$a $b -> $a / $b');
echo "4 / 2\n";
echo $y(4, 2) . "\n\n";

$z = Fn::lambda('/');
echo "4 / 2\n";
echo $z(4, 2) . "\n\n";

