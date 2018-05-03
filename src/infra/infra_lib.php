<?php
class SQLitePDOFactory {
  private $dbFileName;
  private $lockFileName;

  function __construct(
    string $dbFileName, 
    string $lockFileName
  ){
    $this->dbFileName = $dbFileName;
    $this->lockFileName = $lockFileName;
  }

  function getPDOWithoutLock() {
    // 接続
    $pdo = new PDO('sqlite:' . $this->dbFileName);
  
    // SQL実行時にもエラーの代わりに例外を投げるように設定
    // (毎回if文を書く必要がなくなる)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
    // デフォルトのフェッチモードを連想配列形式に設定 
    // (毎回PDO::FETCH_ASSOCを指定する必要が無くなる)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
  }

  function getPDO($funcWithPDO) {
    return $this->lock(function() use($funcWithPDO) {
      $pdo = $this->getPDOWithoutLock();
      return $funcWithPDO($pdo);
    });
  }

  function lock($fuc) {
    if(!file_exists($this->lockFileName)) {
      file_put_contents($this->lockFileName, '');
    }
    $lock_fp = fopen($this->lockFileName ,"w");
    flock($lock_fp, LOCK_EX);
    try {
      return $fuc();
    } finally {
      fclose($lock_fp);
    }
  }
}

class DateTimeFactoryImpl implements DateTimeFactory {
  function now():int {
    return floor(microtime(true) * 1000);
  }
  function requestTime():int {
    return (integer)floor($_SERVER['REQUEST_TIME_FLOAT'] * 1000);
  }
}

class DateTimeFactoryFixed implements DateTimeFactory {
  private $value;
  function __construct(int $value) {
    $this->value = $value;
  }
  function now():int {
    return $this->value;
  }
  function requestTime():int {
    return $this->value;
  }
}

class ResponseFactory {
  function ok($response, $obj = 'ok') {
    $result = [
      'status'=>['status_code'=>200, 'message'=>'ok'],
      'result'=>$obj
    ];
    return $response->withJson($result);
  }
  function ng($response, $exception) {
    // var_dump($exception);
    $result = [
      'status'=>['status_code'=>500, 'message'=>'ng'],
      'error'=>['class'=>get_class($exception), 'message'=>$exception->getMessage()]
    ];
    return $response->withJson($result, 500);
  }
}

class AccessToken extends StringVo {}

interface AuthComponent {
  function auth(AccessToken $accessToken):bool;
}

class AuthComponentByFile implements AuthComponent {
  function auth(?AccessToken $accessToken):bool {
    $accessTokenInFile = file_get_contents('access_token.txt');
    if($accessTokenInFile === false) {
      return true;
    }
    if($accessTokenInFile === $accessToken->value) {
      return true;
    }
    return false;
  }
}