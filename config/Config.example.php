<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 12:13 PM
 */

define('CONFIG',[
    /**
     * WebSocket 服务器配置
     */
    'ws_host' => '0.0.0.0',
    'ws_port' => 9501,

    /**
     * 酷Q HTTP API 插件 HTTP 服务器地址
     *
     * 开头加上 http(s)://
     * 结尾不要加 /
     */
    'CQ_HTTP_url' => 'http://127.0.0.1:5700',

    /**
     * Telegram Bot API Token
     *
     * 用于转发消息的 Bot
     */
    'bot_token' => '123456789:QWERTYUIPASFDGHJKLZXCVBM',

    /**
     * Telegram DeBug Bot API Token
     *
     * 用于发送调试信息的 Bot
     */
    'debug_token' => '123456789:7895n72398nuYUIgyuigf',

    /**
     * Logger Level
     *
     * 5 => None
     * 4 => Error
     * 3 => Warning
     * 2 => Notice
     * 1 => Info
     * 0 => DeBug
     */
    'logger_level' => 3,

    /**
     * Telegram 管理员 Chat ID
     */
    'admin_id' => '346077324',

    /**
     * 群组对应关系设置
     *
     * 键为 QQ 群号
     * 值中键为 chat_id 的为 Telegram Chat ID
     */
    'group_settings' => [
        12345678 => [
            'chat_id' => -87654321,
        ],
    ],

    /**
     * MySQL 数据库配置
     *
     * 目前仅支持 MySQL 及 MariaDB
     */
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'username',
        'password' => 'password',
        'database' => 'database',
    ],

    /**
     * HTTP 代理配置
     *
     * 若不需要代理将 host 留空即可
     */
    'HTTP_proxy_host' => '',
    'HTTP_proxy_port' => 0,

    /**
     * 请求 Telegram API 服务器超时设置(单位s)
     */
    'http_timeout' => 10.0,

    /**
     * 用于加速 QQ 图片海外访问的 CDN (推荐CloudFlare)
     * 可直接用这个
     */
    'image_proxy' => 'http://qq_static_resource.illl.li',

    /**
     * 重启计数器
     *
     * 消息个数达到后重启进程
     */
    'restart_count' => 10000,

    /**
     * 被屏蔽的广告私聊号码
     */
    'blocked_qq' => [
        '2909288299', //腾讯新闻
        '1007807100', //腾讯视频
        '2720152058', //QQ团队
        '2909288299', //天天爱游戏
    ],

    /**
     * 本地 /public/images 目录的外网地址
     *
     * 开头加上 http(s)://
     * 结尾带 /
     */
    'image_provider_url' => 'http://127.0.0.1/images/',

    /**
     * 本地用于储存 Telegram 图片的目录
     */
    'image_folder' => __DIR__ . '/../public/images/',
]);

