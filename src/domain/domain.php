<?php

class StateEntity {
  private $typeAndId;
  private $latestStateEvent;
  private $extraOption;
  private $createDateTime;

  function __construct(
    TypeAndId $typeAndId, 
    StateEvent $latestStateEvent,
    ExtraOption $extraOption,
    CreateDateTime $createDateTime
    ) {
    $this->typeAndId = $typeAndId;
    $this->latestStateEvent = $latestStateEvent;
    $this->extraOption = $extraOption;
    $this->createDateTime = $createDateTime;
  }

  function __get($name){
    $f = getContainsValue($name, 'typeAndId', 'extraOption', 'createDateTime', 'latestStateEvent');
    if($f->isDefined()) {
      $fieldName = $f->get();
      return $this->$fieldName;
    }
    if($name == 'id') {
      return $this->typeAndId->id;
    }
    if($name == 'type') {
      return $this->typeAndId->type;
    }
  }
}

class TypeAndId {
  private $id;
  private $type;

  function __construct(
    Type $type,
    Id $id
    ) {
    $this->type = $type;
    $this->id = $id;
  }

  function __get($name){
    return getContainsValue($name, 'id', 'type')
      ->map(function($v){ return $this->$v; })
      ->getOrThrow(function($v){ return new RuntimeException('not found field'); });
  }
}

class Id extends StringVo {}
class Type extends StringVo {}
class State extends StringVo {}
class Extra extends StringVo {}

class ExtraOption {
  private $valueOption;
  function __construct(?Extra $extra) {
    $this->valueOption = new Option($extra);
  }
  function map($func) {
    return $this->valueOption->map($func);
  }
}

class StateEvent {
  private $state;
  private $createDateTime;
  function __construct(State $state, CreateDateTime $createDateTime) {
    $this->state = $state;
    $this->createDateTime = $createDateTime;
  }
  function __get($name){
    return getContainsValue($name, 'state', 'createDateTime')
      ->map(function($v){ return $this->$v; })
      ->getOrThrow(function($v){ return new RuntimeException('not found field'); });
  }
}
class StateEventList {
  private $list;
  function __construct(array $stateEventList) {
    $this->list = $stateEventList;
  }

  function __get($name){
    if($name == 'list') {
      return $this->list;
    }
    if($name == 'latest') {
      return $this->list[count($this->list) - 1];
    }
  } 
}

class CreateDateTime extends DateTimeVo {}

class StateCreateEvent {
  private $typeAndId;
  private $state;
  private $extraOption;
  private $createDateTime;

  function __construct(
    TypeAndId $typeAndId, 
    State $state,
    ExtraOption $extraOption,
    CreateDateTime $createDateTime
    ) {
    $this->typeAndId = $typeAndId;
    $this->state = $state;
    $this->extraOption = $extraOption;
    $this->createDateTime = $createDateTime;
  }

  function __get($name){
    if($name == 'typeAndId') {
      return $this->typeAndId;
    }
    if($name == 'id') {
      return $this->typeAndId->id;
    }
    if($name == 'type') {
      return $this->typeAndId->type;
    }
    if($name == 'state') {
      return $this->state;
    }
    if($name == 'extraOption') {
      return $this->extraOption;
    }
    if($name == 'createDateTime') {
      return $this->createDateTime;
    }
  }
}

interface ReferRepository {
  function find(TypeAndId $typeAndId):StateEntity;
  function findStateEventList(TypeAndId $typeAndId):StateEventList;
  function findAllIds(Type $type):array;
  function findAllTypeAndIdBefore(CreateDateTime $targetDateTime):array;
}


interface Repository extends ReferRepository {
  function insert(StateCreateEvent $event);
  function update(TypeAndId $typeAndId, State $state, CreateDateTime $createDateTime);
  function delete(array $typeAndIdList);
}


class Service {
  private $repository;
  private $dateTimeFactory;

  function __construct(
    Repository $repository,
    DateTimeFactory $dateTimeFactory
  ) {
    $this->repository = $repository;
    $this->dateTimeFactory = $dateTimeFactory;
  }

  function update(TypeAndid $typeAndId, State $state) {
    $createDateTime = new CreateDateTime($this->dateTimeFactory->requestTime());
    $this->repository->update($typeAndId, $state, $createDateTime);
  }
  
  function clear(int $clearDays) {
    $target = new CreateDateTime($this->dateTimeFactory->requestTime() - $clearDays * 24 * 60 * 60 * 1000);
    $targetList = $this->repository->findAllTypeAndIdBefore($target);
    $this->repository->delete($targetList);
  }
}
