<?php
class Option {
  private $value;
  function __construct($value) {
    $this->value = $value;
  }
  function map($func):Option {
    if($this->isEmpty()) {
      return $this;
    }
    return new Option($func($this->value));
  }
  function forEach($func) {
    if($this->isEmpty()) {
      return $this;
    }
    $func($this->value);
    return $this;
  }
  function isEmpty():bool {
    return $this->value === null;
  }
  function isDefined():bool {
    return !$this->isEmpty();
  }
  function get() {
    if($this->isEmpty()) {
      throw new RuntimeException('value is null');
    }
    return $this->value;
  }
  function getOrElse($defaultvalue) {
    if($this->isEmpty()) {
      return $defaultvalue;
    }
    return $this->value;
  }
  function getOrElseGet($func) {
    if($this->isEmpty()) {
      return $func();
    }
    return $this->value;
  }
  function getOrNull() {
    if($this->isEmpty()) {
      return null;
    }
    return $this->value;
  }
  function getOrThrow($func) {
    if($this->isEmpty()) {
      throw $func();
    }
    return $this->value;
  }
}

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


class Stream {
  private $list;
  function __construct(array $list) {
    $this->list = $list;
  }
  function map($func):Stream {
    $ary = [];
    forEach($this->list as $obj) {
      $ary[] = $func($obj);
    }
    return new Stream($ary);
  }
  function filter($func):Stream {
    $ary = [];
    forEach($this->list as $obj) {
      if($func($obj) === true) {
        $ary[] = $obj;
      }
    }
    return new Stream($ary);
  }
  function forEach($func):Stream {
    forEach($this->list as $obj) {
      $func($obj);
    }
    return $this;
  }
  function toArray():array {
    return $this->list;
  }
  function length():int {
    return count($this->list);
  }
  function size():int {
    return $this->length();
  }
  function count():int {
    return $this->length();
  }
  function isEmpty():bool {
    return $this->length() == 0;
  }
  function toOption():Option {
    if($this->isEmpty()) {
      return new Option(null);
    }
    return new Option($this->list[0]);
  }
  function find($func):Option {
    return $this->filter($func)->toOption();
  }
  static function of(array $ary):Stream {
    return new Stream($ary);
  }
}

function getContainsValue(string $target, string ...$ary):Option {
  return Stream::of($ary)->find(function($v) use($target) { return $v == $target; });
}