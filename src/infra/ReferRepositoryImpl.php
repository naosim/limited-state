<?php

use \naosim\Stream;


class ReferRepositoryImpl implements ReferRepository {
  protected $pdo;
  function __construct(
    PDO $pdo
  ){
    $this->pdo = $pdo;
  }

  function find(TypeAndId $typeAndId):StateEntity {
    $stmt = $this->pdo->prepare("SELECT * FROM insert_event WHERE id = ? AND type = ?");
    $stmt->execute([$typeAndId->id->value, $typeAndId->type->value]);
    $insertEvents = $stmt->fetchAll();

    if(count($insertEvents) == 0) {
      throw new RuntimeException("state not found");
    }
    if(count($insertEvents) > 1) {
      throw new RuntimeException("state too many found");
    }

    return $this->recordToEntity($insertEvents[0]);
  }

  function findStateEventList(TypeAndId $typeAndId):StateEventList {
    $stmt = $this->pdo->prepare("SELECT * FROM update_event WHERE id = ? AND type = ? ORDER BY create_datetime");
    $stmt->execute([$typeAndId->id->value, $typeAndId->type->value]);
    $updateEvents = $stmt->fetchAll();

    $ary = [];
    foreach($updateEvents as $updateEvent) {
      $ary[] = new StateEvent(new State($updateEvent['state']), new CreateDateTime($updateEvent['create_datetime']));
    }
    return new StateEventList($ary);
  }

  function findAll(Type $type):array {
    $stmt = $this->pdo->prepare("SELECT * FROM insert_event WHERE type = ? ORDER BY create_datetime");
    $stmt->execute([$type->value]);
    return Stream::of($stmt->fetchAll())->map(function($v){ return $this->recordToEntity($v); })->toArray();
  }

  function findAllIds(Type $type):array {
    $stmt = $this->pdo->prepare("SELECT id FROM insert_event WHERE type = ?");
    $stmt->execute([$type->value]);
    return Stream::of($stmt->fetchAll())->map(function($v){ return new Id($v['id']); })->toArray();
  }

  function count(): int {
    $stmt = $this->pdo->prepare("SELECT count(*) FROM insert_event");
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  function recordToEntity($record):StateEntity {
    $typeAndId = new TypeAndId(new Type($record['type']), new Id($record['id']));
    $extraOption = new ExtraOption($record['extra'] != null ? new Extra($record['extra']) : null);
    $insertEventCreateDateTime = new CreateDateTime($record['create_datetime']);
    $latestStateEvent = new StateEvent(new State($record['latest_state']), new CreateDateTime($record['update_datetime']));
  
    return new StateEntity($typeAndId, $latestStateEvent, $extraOption, $insertEventCreateDateTime);
  }

  function findAllTypeAndIdBefore(CreateDateTime $targetDateTime):array {
    $stmt = $this->pdo->prepare("SELECT id, type FROM insert_event WHERE create_datetime < ?");
    $stmt->execute([$targetDateTime->value]);
    return Stream::of($stmt->fetchAll())->map(function($v){ return new TypeAndId(new Type($v['type']), new Id($v['id'])); })->toArray();
  }
}