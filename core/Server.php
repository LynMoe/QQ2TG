<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 1:46 PM
 */

require_once __DIR__ . '/Event.php';
require_once __DIR__ . '/Storage.php';

class Server
{
    public function start()
    {
        $int = 0;

        /**
         * 创建必要的MySQL表
         */
        $db = new \Buki\Pdox(CONFIG['database']);
        $db->query("CREATE TABLE if not exists `image_file_id` (`id` int(11) PRIMARY KEY AUTO_INCREMENT, `qq_img_id` text, `qq_img_url` text, `tg_file_id` text, `time` int(11) DEFAULT NULL);");
        $db->query("CREATE TABLE if not exists `user_info` (`id` int(11) PRIMARY KEY AUTO_INCREMENT,`user_id` bigint(20) NOT NULL,`qq_group_id` bigint(20) NOT NULL,`card` text,`flush_time` int(11) NOT NULL);");

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
        $server->on('Message', function (swoole_websocket_server $server, $frame) use($int) {
            if ($int += $int >= CONFIG['restart_count']) exit("\n\n计数 " . $int . " , 结束进程\n\n");
            echo "\n" . $int . "\n";
            echo '--------' . $frame->fd . '--------' . "\n";
            echo "原始数据: \n";
            var_dump($data = json_decode($frame->data,true)); //原始数据
            echo "\n";

            /**
             * 发往 /core/Event.php handler 分析消息类型
             */
            Event::handler($data);
            echo "\n";
        });

        /**
         * 与客户端断开时通知
         */
        $server->on('close', function ($server, $fd) {
            echo "与客户端 {$fd} 号失去连接\n";
        });

        /**
         * 启动WS服务器
         */
        $server->start();
    }
}