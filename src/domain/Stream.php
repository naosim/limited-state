<?php

namespace naosim;

use \naosim\Option;

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