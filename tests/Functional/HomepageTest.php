<?php

namespace Tests\Functional;

class HomepageTest extends BaseTestCase
{

    public function setUp() {
        $this->runDi(function($req, $res){
            $this->repository->dropTableIfExist();
            $this->repository->createTable();
        });
    }

    public function tearDown() {
        $this->runDi(function($req, $res){
            $this->repository->dropTableIfExist();
        });
    }

    public function assertBody($exp, $response, $message = '') {
        $this->assertEquals($exp, (string)$response->getBody(), $message);
    }
    
    public function test_insert() {
        // insert
        $response = $this->runApp('GET', '/api/contract/ids/ID001/insert?state=start&access_token=test', null, function($container){
            $container['dateTimeFactory'] = function($c) {
                return new \DateTimeFactoryFixed(0);
            };
        });
        $this->assertBody(
            '{"status":{"status_code":200,"message":"ok"},"result":"ok"}', 
            $response, 
            'insertが完了する'
        );

        // 参照
        $response = $this->runApp('GET', '/api/contract/ids/ID001?state=start&access_token=test');
        $this->assertBody(
            '{"status":{"status_code":200,"message":"ok"},"result":{"id":"ID001","type":"contract","extra":null,"create_datetime":0,"latest_state_event":{"state":"start","create_datetime":0},"state_event_list":[{"state":"start","create_datetime":0}]}}', 
            $response,
            'insert後参照すると値が取れる'
        );

        // update
        $response = $this->runApp('GET', '/api/contract/ids/ID001/update?state=start&access_token=test', null, function($container){
            $container['dateTimeFactory'] = function($c) {
                return new \DateTimeFactoryFixed(10);
            };
        });
        $this->assertBody(
            '{"status":{"status_code":200,"message":"ok"},"result":"ok"}', 
            $response
        );

        // 参照
        $response = $this->runApp('GET', '/api/contract/ids/ID001?state=start&access_token=test');
        $this->assertBody(
            '{"status":{"status_code":200,"message":"ok"},"result":{"id":"ID001","type":"contract","extra":null,"create_datetime":0,"latest_state_event":{"state":"start","create_datetime":10},"state_event_list":[{"state":"start","create_datetime":0},{"state":"start","create_datetime":10}]}}',
            $response,
            'update後参照すると値が取れる'
        );

        // typeが違うとヒットしない
        $response = $this->runApp('GET', '/api/hoge/ids/ID001?state=start&access_token=test');
        $this->assertBody(
            '{"status":{"status_code":500,"message":"ng"},"error":{"class":"RuntimeException","message":"state not found"}}',
            $response,
            'typeが違うとヒットしない'
        );

        // idが違うとヒットしない
        $response = $this->runApp('GET', '/api/contract/ids/ID002?state=start&access_token=test');
        $this->assertBody(
            '{"status":{"status_code":500,"message":"ng"},"error":{"class":"RuntimeException","message":"state not found"}}',
            $response,
            'idが違うとヒットしない'
        );
    }

    public function test_init_update() {
        // update
        $response = $this->runApp('GET', '/api/contract/ids/ID001/update?state=start&access_token=test', null, function($container){
            $container['dateTimeFactory'] = function($c) {
                return new \DateTimeFactoryFixed(10);
            };
        });
        $this->assertBody(
            '{"status":{"status_code":500,"message":"ng"},"error":{"class":"RuntimeException","message":"data not found"}}', 
            $response,
            '元のデータがない状態で更新するとエラーになる'
        );
    }

    public function test_clear() {
        $response = $this->runApp('GET', '/api/contract/ids/ID001/insert?state=start&access_token=test', null, function($container){
            $container['dateTimeFactory'] = function($c) {
                return new \DateTimeFactoryFixed(1000);
            };
        });
        $response = $this->runApp('GET', '/api/contract/ids/ID002/insert?state=start&access_token=test', null, function($container){
            $container['dateTimeFactory'] = function($c) {
                return new \DateTimeFactoryFixed(2000);
            };
        });
        $response = $this->runApp('GET', '/api/contract/ids/ID003/insert?state=start&access_token=test', null, function($container){
            $container['dateTimeFactory'] = function($c) {
                return new \DateTimeFactoryFixed(3000);
            };
        });

        // 挿入完了
        $response = $this->runApp('GET', '/api/contract/ids?state=start&access_token=test');
        $this->assertBody(
            '{"status":{"status_code":200,"message":"ok"},"result":["ID001","ID002","ID003"]}',
            $response,
            '3件挿入されていること'
        );

        $response = $this->runApp('GET', '/api/contract/ids-detail?state=start&access_token=test');
        $this->assertBody(
            '{"status":{"status_code":200,"message":"ok"},"result":[{"id":"ID001","type":"contract","extra":null,"create_datetime":1000,"latest_state_event":{"state":"start","create_datetime":1000}},{"id":"ID002","type":"contract","extra":null,"create_datetime":2000,"latest_state_event":{"state":"start","create_datetime":2000}},{"id":"ID003","type":"contract","extra":null,"create_datetime":3000,"latest_state_event":{"state":"start","create_datetime":3000}}]}',
            $response,
            '3件挿入されていること'
        );

        // 削除
        $response = $this->runApp('GET', '/api/clear?access_token=test', null, function($container){
            $container['dateTimeFactory'] = function($c) {
                return new \DateTimeFactoryFixed(2000 + 40 * 24 * 60 * 60 * 1000);
            };
        });

        // 参照
        $response = $this->runApp('GET', '/api/contract/ids?state=start&access_token=test');
        $this->assertBody(
            '{"status":{"status_code":200,"message":"ok"},"result":["ID002","ID003"]}',
            $response,
            'ID001が消える'
        );
    }
}