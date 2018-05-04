<?php

use \naosim\Option;
use \naosim\Stream;

class StringVo {
  private $value;
  function __construct(string $value) {
    $this->value = $value;
  }
  function __get($name){
    if($name == 'value') {
      return $this->value;
    }
  }
}

class DateTimeVo {
  private $value;
  function __construct(int $value) {
    $this->value = $value;
  }
  function __get($name){
    if($name == 'value') {
      return $this->value;
    }
  }
  static function days(int $days):int {
    return $days * 24 * 60 * 60 * 1000;
  }
}

interface DateTimeFactory {
  function now():int;
  function requestTime():int;
}

function getContainsValue(string $target, string ...$ary):Option {
  return Stream::of($ary)->find(function($v) use($target) { return $v == $target; });
}