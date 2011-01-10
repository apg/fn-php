<?php
/**
 * Copyright 2009 Andrew Gwozdziewycz <web@apgwoz.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class Fn {
   private static $LAMBDAS = array();
   private static $OPS = array(
      '+', '*', '/', '-', '%', // binop arithmetic
      '&', '|', '^', '<<', '>>', // binop bitwise
      '===', '==', '!=', '<>', '!==', '<', '>', '<=', '>=', // binop compare
      '.', // binop string
      );

   private static $OPS_REGEX = 
      '([+*\/%&|.\-]|<<|>>|===|==|!=|<>|!==|<|>|<=|>=)';

   /**
    * Returns the composition of the functions f and g
    *
    * compose(function, function) -> function 
    */
   public static function compose($f, $g) {
      if (is_callable($f) && is_callable($g)) {
         return function() use ($f, $g) {
            $args = func_get_args();
            $result = call_user_func_array($g, $args);
            return $f($result);
         };
      }
      throw new Exception('arguments to compose must be callable');
   }

   /**
    * Computes function for each element of passed in arrays
    *
    * If multiple arrays are passed, function is called with next element in
    * array from each array
    *
    * map(function, array1[... arrayN]) -> array
    */
   public static function map() {
      $args = func_get_args();
      if (count($args) > 1) {
        return call_user_func_array('array_map', $args);
      }
      return array();
   }

   /**
    * Computes function for each element in the list, calling it with
    * the successive result of the function call. left to right down the array
    *
    * function should be (accumulated, next)
    * 
    * foldl(function, initialvalue, array) -> value
    */
   public static function foldl() {
      $args = func_get_args();
      if (count($args) == 3) {
        return array_reduce($args[2], $args[0], $args[1]);
      }
      throw new Exception("foldl must be called with 3 arguments");
   }

   /**
    * Computes function for each element in the list, calling it with
    * the successive result of the function call. right to left down the array
    *
    * function should be (accumulated, next)
    *
    * foldl(function, initialvalue, array) -> value
    */
   public static function foldr() {
      $args = func_get_args();
      if (count($args) == 3) {
        return array_reduce(array_reverse($args[2]), $args[0], $args[1]);
      }
      throw new Exception("foldl must be called with 3 arguments");
   }

   /**
    * Returns an array the combined arguments
    *
    * zip(array1, array2[... arrayN]) -> array(array(a1[0], a2[0]...an[0])...)
    */
   public static function zip() {
      $args = func_get_args();
      $argscount = count($args);

      if ($argscount < 2) {
         throw new Exception("zip requires at least 2 array arguments");
      }

      if (Fn::every('is_array', $args)) {
         $result = array();
         foreach ($args[0] as $key=>$val) {
            $elems = array($val);
            for ($i = 1; $i < $argscount; $i++) {
               if (!array_key_exists($key, $args[$i])) {
                  throw new Exception("array arguments to zip not identical " .
                                      "in keys");
               }
               $elems[] = $args[$i][$key];
            }
            $result[] = $elems;
         }
         return $result;
      }
      throw new Exception("zip requires arrays as it's arguments");
   }

   /**
    * Returns the elements in the array where the predicate function is true
    *
    * filter(function, array) -> array
    */
   public static function filter() {
      $args = func_get_args();
      return array_filter($args[1], $args[0]);
   }

   /**
    * Returns a function which tests if calling all the functions with the
    * argument are true
    *
    * andf(function1, function2[... functionN]) -> function -> bool
    */
   public static function andf() {
      $fns = func_get_args();
      if (count($fns) && Fn::every('is_callable', $fns)) {
         return function($x) use ($fns) {
            foreach ($fns as $fn) {
               $tmp = $fn($x);
               if ($tmp === false || $tmp === 0) {
                  return false;
               }
            }
            return true;
         };
      }
      throw new Exception("andf expects arguments to all be callable");
   }

   /**
    * Returns a function which tests if calling one the functions with the
    * argument is true (short circuits)
    *
    * andf(function1, function2[... functionN]) -> function -> bool
    */
   public static function orf() {
      $fns = func_get_args();
      if (count($fns) && Fn::every('is_callable', $fns)) {
         return function($x) use ($fns) {
            foreach ($fns as $fn) {
               $tmp = $fn($x);
               if ($tmp !== false && $tmp !== 0) {
                  return true;
               }
            }
            return false;
         };
      }
      throw new Exception("andf expects arguments to all be callable");
   }

   /**
    * Returns a function which when called returns the boolean opposite of the
    * return value
    *
    * not(function) -> function -> bool
    */
   public static function not($fn) {
      if (is_callable($fn)) {
         return function($x) use ($fn) {
            $tmp = $fn($x);
            if ($tmp === false || $tmp === 0) {
               return true;
            }
            return false;
         };
      }
      throw new Exception("not expects arguments to all be callable");
   }

   /**
    * Returns true if function returns true for any of the arguments
    *
    * any(function, array) -> boolean
    */
   public static function any() {
      $args = func_get_args();
      if (count($args) == 2 && is_callable($args[0])) {
         $func = $args[0];
         foreach ($args[1] as $arg) {
            $tmp = $func($arg);
            if ($tmp) {
               return true;
            }
         }
         return false;
      }
      throw new Exception("some expects a function and an array");
   }

   /**
    * Returns true if and only if the predicate is true for each element
    *
    * every(function, array) -> boolean
    */
   public static function every() {
      $args = func_get_args();
      if (count($args) == 2 && is_callable($args[0])) {
         $func = $args[0];
         foreach ($args[1] as $arg) {
            $tmp = $func($arg);
            if ($tmp === 0 && $tmp === false) {
               return false;
            }
         }
         return true;
      }
      throw new Exception("every expects a function and an array");
   }

   /**
    * Returns a function which "saves" the first argument to be used when
    * the returned function is called.
    *
    * partial(function, value) -> function -> value
    */
   public static function partial() {
     $args = func_get_args();
     return function () use ($args) {
       $newargs = func_get_args();
       call_user_func_array($args[0], array_shift($args) + $newargs);
     };
   }

   /**
    * Returns the argument passed in. The identity function. 
    *
    * I(value) -> value
    */
   public static function I($val) {
      return $val;
   }

   /**
    * Returns a function which returns the value passed
    *
    * K(value) -> function -> value
    */
   public static function K($val) {
      return function() use ($val) {
         return $val;
      };
   }

   /** 
    * Returns a function which calls the second function with the arguments
    * and then calls the first function with the result + arguments
    *
    * S(function, function) -> function -> value
    */
   public static function S($f, $g) {
      return function() use ($f, $g) {
         $args = func_get_args();
         $result = call_user_func_array($g, $args);
         array_unshift($args, $result);
         return call_user_func_array($f, $args);
      };
   }

   /**
    * Returns a function which calls the passed in function with the first
    * two arguments flipped.
    *
    * flip(function) -> function -> value
    */
   public static function flip($fn) {
      if (is_callable($fn)) {
         return function() use ($fn) {
            $args = func_get_args();
            if (count($args) >= 2) {
               $tmp = $args[1];
               $args[1] = $args[0];
               $args[0] = $tmp;
            }
            return call_user_func_array($fn, $args);
         };
      }
      throw new Exception("argument to flip must be callable");
   }

   /**
    * Returns a function created via a string
    *
    * lambda(string) -> function -> value
    */
   public static function lambda($str) {
      if (array_key_exists($str, self::$LAMBDAS)) {
         return self::$LAMBDAS[$str];
      }

      if (self::is_op($str)) {
         $fn = self::create_op($str);
      }
      else if (strpos($str, '->') !== FALSE) {
         $fn = self::create_fun($str);
      }
      else {
         throw new Exception('not a proper string lambda');
      }

      self::$LAMBDAS[$str] = $fn;
      return $fn;
   }

   private static function is_op($str) {
      return preg_match('/^' . self::$OPS_REGEX . '/', trim($str));
   }

   private static function create_op($str) {
      if (preg_match('/^' . self::$OPS_REGEX . '$/', trim($str))) {
         return create_function('$x,$y', 'return $x ' . $str . '$y' . ';');
      }
      return create_function('$x', 'return $x' . $str . ';');
   }

   private static function create_fun($str) {
      list($args, $body) = split('->', $str);

      if (strpos($args, ',') === FALSE) {
         $args = preg_replace('/\s+/', ',', trim($args));
      }

      if (!preg_match('/^return.*/', $body)) {
         $body = 'return ' . $body;
      }
      if (!preg_match('/.*;$/', $body)) {
         $body .= ';';
      }
      return create_function($args, $body);
   }
}
