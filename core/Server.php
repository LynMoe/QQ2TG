<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 1:46 PM
 */

require_once __DIR__ . '/Event.php';

class Server
{
    public function start()
    {
        /**
         * 新建WS服务器
         */
        $server = new swoole_websocket_server(CONFIG['ws_host'], CONFIG['ws_port']);

        /**
         * 与客户端握手时通知
         */
        $server->on('open', function (swoole_websocket_server $server, $request) {
            echo "与客户端 {$request->fd} 号成功握手\n";
        });

        /**
         * [Main]客户端发送消息时
         */
        $server->on('Message', function (swoole_websocket_server $server, $frame) {
            echo '--------' . $frame->fd . '--------' . "\n";
            echo "原始数据: \n";
            var_dump(json_decode($frame->data,true)); //原始数据
            echo "\n";
            /**
             * 处理消息内容
             */
            Event::handler(json_decode($frame->data,true));
            echo "\n\n";
        });

        /**
         * 与客户端断开时通知
         */
        $server->on('close', function ($server, $fd) {
            echo "与客户端 {$fd} 号失去连接\n";
        });

        /**
         * 启动W服务器
         */
        $server->start();
    }
}