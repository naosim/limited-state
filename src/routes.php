<?php
use Slim\Http\Request;
use Slim\Http\Response;



$app->get('/api/{type}/ids/{id}/insert', function (Request $request, Response $response, array $args) {
  $params = new CommonRequestParams($args, $request);
  $event = new StateCreateEvent(
    $params->getTypeAndId(), 
    $params->getState(), 
    $params->getExtraOption(), 
    new CreateDateTime($this->dateTimeFactory->requestTime())
  );
  
  $this->repository->insert($event);

  return $this->responseFactory->ok($response);
});

$app->get('/api/{type}/ids/{id}/update', function (Request $request, Response $response, array $args) {
  $params = new CommonRequestParams($args, $request);
  $this->service->update($params->getTypeAndId(), $params->getState());

  return $this->responseFactory->ok($response);
});

$app->get('/api/{type}/ids/{id}', function (Request $request, Response $response, array $args) {
  $params = new CommonRequestParams($args, $request);
  $typeAndId = $params->getTypeAndId();

  $entity = $this->referRepository->find($typeAndId);
  $stateEventList = $this->referRepository->findStateEventList($typeAndId);
  $result = entityToObj($entity);
  $result["state_event_list"] = stateEventListToObj($stateEventList);

  return $this->responseFactory->ok($response, $result);
});

$app->get('/api/{type}/ids', function (Request $request, Response $response, array $args) {
  $params = new CommonRequestParams($args, $request);

  $result = $this->referRepository->findAllIds($params->getType());
  
  return $this->responseFactory->ok($response, Stream::of($result)->map(function($v){ return $v->value; })->toArray());
});

$app->get('/api/{type}/ids-detail', function (Request $request, Response $response, array $args) {
  $params = new CommonRequestParams($args, $request);

  $result = $this->referRepository->findAll($params->getType());
  
  return $this->responseFactory->ok($response, Stream::of($result)->map(function($v){ return entityToObj($v); })->toArray());
});

$app->get('/api/clear', function (Request $request, Response $response, array $args) {
  $this->service->clear($this->get('settings')['application']['clear_days']);
  return $this->responseFactory->ok($response);
});

$app->get('/api/createtable', function (Request $request, Response $response) {
  $this->repository->createTable();
  return $this->responseFactory->ok($response);
});

$app->get('/api/count', function (Request $request, Response $response, array $args) {
  $params = new CommonRequestParams($args, $request);

  $result = $this->referRepository->count();
  
  return $this->responseFactory->ok($response, $result);
});

$app->get('/', function (Request $request, Response $response) {
  return $this->renderer->render($response, 'index.phtml');
});
