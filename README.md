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
  - ボディ: `{"status":{"status_code":200,"message":"ok"},"result":#APIに応じた結果#}`
  - 下記APIごとの仕様ではresultの値を記す
- 異常系
  - ステータスコード:200以外
  - ボディ: `{"status":{"status_code":#スタータスコードと同じ値#,"message":"ng"},"error":{"class":"#例外クラス名#","message":"#例外メッセージ#"}}`


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
`"ok"`

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
`"ok"`

##### 異常
- 必須項目が無い
- 更新するデータがヒットしない

### 状態の取得
#### リクエスト
URL: `/api/{type}/ids/{id}`

#### レスポンス
##### 正常
```
{"id":"ID001","type":"contract","extra":null,"create_datetime":0,"latest_state_event":{"state":"start","create_datetime":10},"state_event_list":[{"state":"start","create_datetime":0},{"state":"start","create_datetime":10}]}
```

key | 必須 | 型 | value
---|---|---|---
id | o | string | 見つかった状態のid
type | o | string | 見つかった状態のtype
extra | | string | 状態の追加時に指定した自由項目。無い場合はnull
create_datetime | o | number | 追加した日時
latest_state_event | o | string | 最新の状態
latest_state_event.state | o | string | 最新の状態
latest_state_event.create_datetime | o | number | 最新の状態になった日時
state_event_list | o | array | 過去の状態イベントリスト(日時の昇順)。必ず1つ以上ある
state_event_list[n].state | o | string | 状態
state_event_list[n].create_datetime | o | number | 日時


##### 異常
- データがヒットしない

### タイプに属するID一覧の取得
#### リクエスト
URL: `/api/{type}/ids`
#### レスポンス
##### 正常
```
["ID001","ID002","ID003"]
```
ヒットしたIDがリストで取れる  
ヒットしなかった場合は空リストを返す

##### 異常
なし

