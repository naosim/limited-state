<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
  $settings = $c->get('settings')['renderer'];
  return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
  $settings = $c->get('settings')['logger'];
  $logger = new Monolog\Logger($settings['name']);
  $logger->pushProcessor(new Monolog\Processor\UidProcessor());
  $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
  return $logger;
};

$container['mqPdoFactory'] = function($c) {
  return new SQLitePDOFactory(__DIR__ . '/../my_sqlite_db.db', 'lock');
};

$container['dateTimeFactory'] = function($c) {
  return new DateTimeFactoryImpl();
};
$container['responseFactory'] = function($c) {
  return new ResponseFactory();
};

$container['errorHandler'] = function ($c) {
  return function ($request, $response, $exception) use ($c) {
    return $c->responseFactory->ng($response, $exception);
  };
};