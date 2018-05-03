# MQLite

SQLiteを使った適当な状態管理基盤です

## Setup

### pear install

### access_token file
`/public/access_token.txt`に任意のアクセストークンを保存する  
初期値はtest

### deploy
FTPサーバ等にアップロードする

### logsディレクトに書き込み権限をつける

### DB setup
テーブルを生成する
```
/public/api/createtable?access_token=[access_token]
```

## memo
### ローカルで動かす
```
php -S localhost:8080 -t public public/index.php
```

### テストする
```
php composer.phar test
```
## Usage
### 共通
- 日時に相当する値は`1970/01/01からのミリ秒`で表現する
#### リクエスト
- HTTPのメソッドは全て`GET`
- すべてのAPIのqueryに`access_token=[access_token]`が必要
#### レスポンス
- 形式: JSON
- 正常系
  - ステータスコード: 200
  - ボディ:

json | 必須 | 型 | value
---|---|---|---
{ | | object |
&emsp;"status": { | o | object |
&emsp;&emsp;"status_code":200, | o | number | 200固定
&emsp;&emsp;"message": "ok" | o | string | "ok"固定
&emsp;}, | | |
&emsp;"result": * | o | any | APIに応じた結果が入る。下記APIごとのレスポンスはこの値部分のみを記す
} | | |

  - 下記APIごとの仕様ではresultの値を記す
- 異常系
  - ステータスコード:200以外
  - ボディ:

json | 必須 | 型 | value
---|---|---|---
{ | | object |
&emsp;"status": { | o | object |
&emsp;&emsp;"status_code":500, | o | number | HTTPステータスコードと同じ値が入る。異常系のため200以外
&emsp;&emsp;"message": "ng" | o | string | "ng"固定
&emsp;}, | | |
&emsp;"error": { | o | object | エラーの詳細
&emsp;&emsp;"class": "RuntimeException" | o | string | 例外クラス名
&emsp;&emsp;"message": "not found" | o | string | 例外メッセージ
&emsp;} | | |
} | | |


### 状態の追加
#### リクエスト
URL: `/api/{type}/ids/{id}/insert?state=[state]&extra=[extra]`

パラメータ | 必須 | 説明
---|---|---
type | o | typeとidで一意になる任意の値
id | o | typeとidで一意になる任意の値
state | o | 初期状態の名前
extra | | 自由項目

#### レスポンス
##### 正常
json | 必須 | 型 | value
---|---|---|---
"ok" | o | string | "ok"固定


##### 異常
- 必須項目が無い
- typeとidの組み合わせが重複

### 状態の更新
#### リクエスト
URL: `/api/{type}/ids/{id}/insert?update=[state]`

パラメータ | 必須 | 説明
---|---|---
type | o | 更新したいtype
id | o | 更新したいid
state | o | 任意の状態

#### レスポンス
##### 正常
json | 必須 | 型 | value
---|---|---|---
"ok" | o | string | "ok"固定

##### 異常
- 必須項目が無い
- 更新するデータがヒットしない

### 状態の取得
#### リクエスト
URL: `/api/{type}/ids/{id}`

#### レスポンス
##### 正常

json | 必須 | 型 | value
---|---|---|---
{ | |object |
&emsp;"id": "ID001", | o | string | 見つかった状態のid
&emsp;"type": "contract", | o | string | 見つかった状態のtype
&emsp;"extra": null, | | string | 状態の追加時に指定した自由項目。無い場合はnull
&emsp;"create_datetime": 0, | o | number | 最初に生成された日時
&emsp;"latest_state_event": { | o | string | 最新の状態
&emsp;&emsp;"state": "start", | o | string | 最新の状態
&emsp;&emsp;"create_datetime":10 | o | number | 最新の状態になった日時
&emsp;"state_event_list": [ | o | array | 過去の状態イベントリスト(日時の昇順)。要素の数は1以上
&emsp;&emsp;{ | | object | 状態と日時
&emsp;&emsp;&emsp;"state": "start", | o | string | 状態
&emsp;&emsp;&emsp;"create_datetime":0 | o | number | 日時
&emsp;&emsp;} |
&emsp;] |
} |


##### 異常
- データがヒットしない

### タイプに属するID一覧の取得
#### リクエスト
URL: `/api/{type}/ids`
#### レスポンス
##### 正常
json | 必須 | 型 | value
---|---|---|---
[ | o | array | IDの配列。数は0以上。ヒットしなかった場合は0
&emsp;"ID001" | o | string | ID
] | | |

ヒットしたIDがリストで取れる  
ヒットしなかった場合は空リストを返す

##### 異常
なし

