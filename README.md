
# QQ2TG

一个帮助 QQ 与 Telegram 互联的小程序
![qq2tg.png](https://i.loli.net/2018/07/22/5b543094633b6.png)

## 演示
![Snipaste 2018-07-22 14-56-41.png](https://i.loli.net/2018/07/22/5b5430944d542.png)
![Snipaste 2018-07-22 14-57-56.png](https://i.loli.net/2018/07/22/5b54309451a4b.png)
![Snipaste 2018-07-22 14-58-53.png](https://i.loli.net/2018/07/26/5b58a0015f4a6.png)
![Snipaste 2018-07-22 15-04-23.png](https://i.loli.net/2018/07/22/5b543093ec26d.png)
![Snipaste 2018-07-22 15-05-43.png](https://i.loli.net/2018/07/22/5b5430940f0d7.png)

## 现况

说真的我现在已经不知道应该做什么了，感觉这个项目的已经到头了，几个问题到目前为止还没有什么好的解决方案(有些是酷Q的坑)
要是各位有什么想法或者方案的话欢迎在 Telegram `@XiaoLin0815` 或者 QQ `845211975` 上骚扰我(讲道理issue也不是不行) ))

## 灵感

最近新入手了一台万普拉斯，艹了几天之后发现除了屏幕外就是QQ最耗电了，几乎占了7%，再加上以前就有这个念头，又正好是暑假，就挖了这个无底洞

## 简述

这个程序主体使用`PHP`编写，依赖于拓展`swoole`，配合酷Q的[coolq-http-api](https://github.com/richardchien/coolq-http-api)插件使用

目前还不是十分完善，可能存在消息转发不及时、各种报错，还请各位 dalao 指点一二

```text
联系了一下 coolq-http-api 插件的作者, 得知酷Q现在还不支持消息回复功能, 所以这个项目的原生群组消息回复的功能应该会无限期延长, 望谅解
```

## Feature

- 使用 Swoole 的异步 HTTP Client 请求 Telegram Bot API 服务器
- 将QQ图片缓存为 Telegram File ID , 提高效率
- 拓展性较高, 可轻松支持一个新的 CQ 码或 Telegram 消息格式
- 支持双向发起私聊消息
- 支持 QQ 自带的 Emoji
- 支持 Telegram 端撤回 QQ 消息
- 支持私聊 QQ 黑名单
- 支持 Sticker 无损转发 (可以当 Sticker 转移机器人啦233
- 采用世界上最好的语言编写  ((日常拉仇恨

实在编不出来了...  /滑稽

## 使用

1. 将代码拖到本地 :  ```git clone https://github.com/XiaoLin0815/QQ2TG.git```
2. 将`config\Config.example.php`改名为`config\Config.php`并填写完整

    键           |值
    :------------|-------
     `ws_host`/`ws_port` |  `String/Int` 本地`websocket`的主机和端口
     `CQ_HTTP_url` |  `String` 酷Q HTTP-API的HTTP服务器地址
     `bot_token` |  `String` Telegram Bot API token, Telegram `@BotFather`获取
     `admin_id` |  `Int` Telegram 管理员的 `chat_id`, 目前用于发送性能调试数据
     `group_settings` |  `Array` 配置QQ群组与Telegram群组的对应关系, 请按照示例添加
     `database` |  `Array` MySQL数据库基本信息(后续可能会支持更多数据库)
     `HTTP_proxy_host/port` |  `String/Int` HTTP代理, 用于请求Telegram服务器(不需要请留空host)
     `http_timeout` |  `Int` 请求超时时间(秒)
     `image_proxy` |  `String` QQ 图片服务器海外CDN (推荐CloudFlare)
     `restart_count` |  `Int` 到达数目后退出进程, 若未设置进程守护请设置为无穷大(999999999999)
     `image_provider_url` | `String` `webhook.php` 同级目录下的 `images` 目录访问地址
     `image_folder` | `String` Telegram 图片的缓存文件夹
3. 安装酷Q(若要发送图片则要求安装Pro版本)以及[coolq-http-api](https://github.com/richardchien/coolq-http-api)插件，并添加配置以下参数:
    - use_ws_reverse :  使用反向 WebSocket 通讯
    - ws_reverse_api_url/ws_reverse_event_url ： 反向WS服务器地址，对应操作2中配置的`ws_host`/`ws_port`
    - host/port/use_http :  HTTP服务器设置，对应操作2中配置的`CQ_HTTP_url`
    ```json
    "use_ws_reverse": true,
    "ws_reverse_api_url": "ws://192.168.31.120:9501",
    "ws_reverse_event_url": "ws://192.168.31.120:9501",
    "host": "0.0.0.0",
    "port": 5700,
    "use_http": true,
    ```
4. 确保您本地配置好科学上网工具或填写好了`HTTP_proxy_host/port`(若不需要请留空)
5. 确保您的PHP已安装了`swoole`扩展
6. 进入目录, 输入```composer update```
7. 输入```php run.php```
8. 在网站环境中设置 `/public` 为运行目录
9. 访问 `https://api.telegram.org/bot<bot_token>/setWebHook?url=https://<Your_URL>/webhook.php` 设置WebHook, 若认为不安全, 可自行改变文件名
10. 配置进程守护程序(**强烈建议**):
    - Systemd
        ```bash
        # /usr/lib/systemd/system/QQ2TG.service
        [Unit]
        Description=QQ2TG
        Documentation=https://github.com/XiaoLin0815/QQ2TG
        After=network.target
        [Service]
        ExecStart=/path/to/your/php /path/to/QQ2TG/run.php
        Restart=always
        [Install]
        WantedBy=multi-user.target
        ```
11. 终端输入 ```service QQ2TG start```
12. enjoy it

- TG端发送消息:
    1.私聊机器人并发送 `/new_chat`
    2.选择要私聊的用户
    3.回复机器人发出的消息

## 问题

若要在 Linux 上使用 酷Q , 可参考[这里](https://github.com/CoolQ/docker-wine-coolq)

现在可能会出现消息**错乱**等情况(比如 QQ 在图片后的消息会在 TG 出现在图片前)，应该会在以后的版本中进行修正  ((没思路, 求PR

若要修改消息样式，可前往 `core\Event\(Private/Group)Message.php` 自行修改

QQ中的原创表情(不包括漫游表情等图片表情)暂未找到方法获取，若找到方法会添加的

## TODO

- ~~异步消息发送~~
- ~~图片信息缓存~~
- ~~用户群名片缓存~~
- ~~支持私聊消息~~
- ~~支持消息撤回~~
- 解决文档中提到的问题
  - 消息错乱
  - 私聊消息 Timeout
  - ~~从 Telegram 端发起私聊~~
- 更多CQ码兼容

dalao们如果有任何问题或者建议请在issue中提出或直接提交PR，感谢万分

## 题外话

毕竟初中生, 就是头脑简单四肢发达, 思维算法一坨屎, 英语极差, 导致代码里很多奇怪或者重复的变量名, 还请多多包涵,有些地方代码质量不高、效率低下, 或者结构有问题的, 还请 dalao 们多多指点

或许真的要像[LWL](https://lwl.moe)说的那样`变得更优秀`吧

## 更新日志

2018/07/26 支持 Sticker 本地处理转发

2018/07/22 支持 Telegram 端先发起私聊

2018/07/20 支持消息撤回

2018/07/18 回复消息提醒对方

2018/07/12 支持双向私聊消息

2018/07/11 更新名片获取机制, 添加伪消息回复支持

2018/07/05 添加图片信息缓存、消息异步发送、群名片缓存, 支持保存消息

2018/07/02 第一版，支持 QQ 与 Telegram 消息双向互通，支持图片、sticker 发送