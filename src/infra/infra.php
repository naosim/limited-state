<?php
class RepositoryImpl implements Repository {
  private $pdo;
  function __construct(
    PDO $pdo
  ){
    $this->pdo = $pdo;
  }

  function insert(StateCreateEvent $event) {
    $extra = $event->extraOption->map(function($v){ return $v->value; })->getOrNull();
    $stmt = $this->pdo->prepare('insert into insert_event (id, type, extra, create_datetime, latest_state, update_datetime) values (?, ?, ?, ?, \'none\', 0)');
    $stmt->execute([$event->id->value, $event->type->value, $extra, $event->createDateTime->value]);
    $this->update($event->typeAndId, $event->state, $event->createDateTime);
  }
  function update(TypeAndId $typeAndId, State $state, CreateDateTime $createDateTime) {
    $stmt = $this->pdo->prepare('update insert_event SET latest_state = ?, update_datetime = ? WHERE id = ? AND type = ?');
    $stmt->execute([$state->value, $createDateTime->value, $typeAndId->id->value, $typeAndId->type->value]);
    if($stmt->rowCount() == 0) {
      throw new RuntimeException('data not found');
    }

    $stmt = $this->pdo->prepare('insert into update_event (id, type, state, create_datetime) values (?, ?, ?, ?)');
    $stmt->execute([$typeAndId->id->value, $typeAndId->type->value, $state->value, $createDateTime->value]);
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

  function delete(array $typeAndIdList) {
    $this->pdo->beginTransaction();
    try {
      Stream::of($typeAndIdList)
        ->map(function($v) { return [$v->id->value, $v->type->value]; })
        ->forEach(function($v) {
          $stmt = $this->pdo->prepare("DELETE FROM insert_event WHERE id = ? AND type = ?");
          $stmt->execute($v);
          $stmt = $this->pdo->prepare("DELETE FROM update_event WHERE id = ? AND type = ?");
          $stmt->execute($v);
        });
      $this->pdo->commit();
    } catch(Exception $e) {
      $this->pdo->rollBack();
    }
    
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

  function findAllIds(Type $type):array {
    $stmt = $this->pdo->prepare("SELECT id FROM insert_event WHERE type = ?");
    $stmt->execute([$type->value]);
    return Stream::of($stmt->fetchAll())->map(function($v){ return new Id($v['id']); })->toArray();
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

  function createTable() {
    $this->pdo->exec("CREATE TABLE insert_event(
      id TEXT NOT NULL,
      type TEXT NOT NULL,
      extra TEXT,
      create_datetime INTEGER NOT NULL,
      latest_state TEXT NOT NULL,
      update_datetime INTEGER NOT NULL,
      UNIQUE(id, type)
    )");//CREATE INDEX インデックス名 ON テーブル名(カラム名1, カラム名2, ...);
    $this->pdo->exec("CREATE INDEX i_insert_event_id ON insert_event (id)");
    $this->pdo->exec("CREATE INDEX i_insert_event_type ON insert_event (type)");
    $this->pdo->exec("CREATE TABLE update_event(
      id TEXT NOT NULL,
      type TEXT NOT NULL,
      state TEXT NOT NULL,
      create_datetime INTEGER NOT NULL
    )");
    $this->pdo->exec("CREATE INDEX i_update_event_id ON update_event (id)");
    $this->pdo->exec("CREATE INDEX i_update_event_type ON update_event (type)");
  }

  function dropTableIfExist() {
    Stream::of([
      'insert_event', 
      'update_event'
    ])->forEach(function($table){ $this->pdo->exec("DROP TABLE IF EXISTS " . $table); });

    Stream::of([
      'i_insert_event_id', 
      'i_insert_event_type',
      'i_update_event_id', 
      'i_update_event_type'
    ])->forEach(function($index){ $this->pdo->exec("DROP INDEX IF EXISTS " . $index); });
  }
}

function entityToObj(StateEntity $entity) {
  return [
    'id'=>$entity->id->value,
    'type'=>$entity->type->value,
    'extra'=>$entity->extraOption->map(function($v){ return $v->value; })->getOrNull(),
    'create_datetime'=>$entity->createDateTime->value,
    'latest_state_event'=> stateEventToObj($entity->latestStateEvent)
  ];
}

function stateEventListToObj(StateEventList $list) {
  return Stream::of($list->list)->map(function($v){ return stateEventToObj($v); })->toArray();
}

function stateEventToObj(StateEvent $event) {
  return [
    'state'=>$event->state->value,
    'create_datetime'=>$event->createDateTime->value
  ];
}

function dictToTypeAndId($dict) {
  return new TypeAndId(new Type($dict['type']), new Id($dict['id']));
}

class CommonRequestParams {
  private $args;
  private $request;

  function __construct(array $args, Slim\Http\Request $request) {
    $this->args = $args;
    $this->request = $request;
  }

  function getType():Type {
    return new Type($this->args['type']);
  }

  function getId():Id {
    return new Id($this->args['id']);
  }

  function getTypeAndId():TypeAndId {
    return new TypeAndId($this->getType(), $this->getId());
  }

  function getState():State {
    return new State($this->request->getQueryParam('state'));
  }

  function getExtraOption(): ExtraOption {
    $e = $this->request->getQueryParam('extra');
    return $e != null ? new ExtraOption(new Extra($e)) : new ExtraOption(null);
  }

}