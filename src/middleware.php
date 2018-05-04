<?php
// Application middleware

// log
$app->add(function ($request, $response, $next) {
  $this->logger->info($request->getUri()->getPath());
	$response = $next($request, $response);
	return $response;
});

function getAccessTokenFromRequest($request) {
  if($request->getQueryParam('access_token') != null) {
    return $request->getQueryParam('access_token');
  }
  if($request->getHeader('access_token') != null) {
    return $request->getHeader('access_token');
  }
  return null;
}

function isTestMode(string $accessToken) {
  return $accessToken == null || $accessToken == 'test';
}

// auth
$app->add(function ($request, $response, $next) {
  $accessTokenFile = __DIR__ . '/../access_token.txt';
  if(strpos($request->getUri()->getPath(), 'api') === false || !file_exists($accessTokenFile)) {
    return $next($request, $response);
  }

  $accessToken = file_get_contents($accessTokenFile);

  $t = getAccessTokenFromRequest($request);
  
  if(!isTestMode($t) && $t != $accessToken) {
    throw new RuntimeException('unmatch access_token');
  }
	$response = $next($request, $response);
	return $response;
});

// pdo
$app->add(function ($request, $response, $next) {
  if(strpos($request->getUri()->getPath(), 'api') === false) {
    return $next($request, $response);
  }

  $accessToken = getAccessTokenFromRequest($request);
  $dbFileName = isTestMode($accessToken) ? __DIR__ . '/../test_db.db' : __DIR__ . '/../my_sqlite_db.db';
  return $this->mqPdoFactory->getPDO($dbFileName, function($pdo) use($request, $response, $next) {
    $this['pdo'] = $pdo;
    $this['repository'] = new RepositoryImpl($pdo);
    $this['referRepository'] = new ReferRepositoryImpl($pdo);
    $this['service'] = new Service($this['repository'], $this->dateTimeFactory);
    return $next($request, $response);
  });
});