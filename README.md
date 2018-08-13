# QQ2TG

一个帮助 QQ 与 Telegram 互联的小程序
![qq2tg.png](https://i.loli.net/2018/07/22/5b543094633b6.png)

## 演示

![Snipaste 2018-08-09 15-11-13.png](https://i.loli.net/2018/08/09/5b6be92422320.png)
![Snipaste 2018-08-09 14-23-51.png](https://i.loli.net/2018/08/09/5b6be81161b02.png)

## 简述

程序主体使用`PHP`编写, 依赖于拓展`swoole`, 配合酷Q的[coolq-http-api](https://github.com/richardchien/coolq-http-api)插件使用

目前还不是十分完善, 可能存在各种奇怪的问题, 还请各位 dalao 指点一二

## Feature

- 使用 Swoole 的异步 HTTP Client 请求 Telegram Bot API 服务器
- 将QQ图片缓存为 Telegram File ID , 提高效率
- 拓展性较高, 可轻松支持一个新的 CQ 码或 Telegram 消息格式
- 支持双向发起私聊消息
- 支持 QQ 自带的 Emoji
- 支持 Telegram 端撤回 QQ 消息
- 支持私聊 QQ 黑名单
- 支持 Sticker 无损转发 (可以当 Sticker 转移机器人啦233
- 附带 Web 端消息查看
- 支持位置信息发送与接收
- 机器人在线时手机电脑可同时在线
- 采用世界上最好的语言编写  ((日常拉仇恨

实在编不出来了...  /滑稽

## 使用

1. 将代码拖到本地 :  ```git clone https://github.com/XiaoLin0815/QQ2TG.git```
2. 将`config\.env.example`改名为`config\.env`并根据提示及示例填写完整
3. 安装酷Q(若要发送图片则要求安装Pro版本)以及[coolq-http-api](https://github.com/richardchien/coolq-http-api)插件，并添加配置以下参数:
  - 酷Q:
      - 更改登录模式为平板模式
        ```ini
        [Debug]
        Platform=2
        ```
    - HTTP API:
      - use_ws_reverse :  使用反向 WebSocket 通讯
      - ws_reverse_api_url/ws_reverse_event_url ： 反向WS服务器地址，对应操作2中配置的`websocket -> host/port`
      - host/port/use_http :  HTTP服务器设置，对应操作2中配置的`coolq -> http_url`
        ```json
        {
            "use_ws_reverse":true,
            "ws_reverse_api_url":"ws://192.168.31.120:9501",
            "ws_reverse_event_url":"ws://192.168.31.120:9501",
            "host":"0.0.0.0",
            "port":5700,
            "use_http":true
        }
        ```
4. 确保您本地可访问 Telegram Bot API 服务器或填写好了`proxy -> host/port`(若不需要请留空)
5. 确保您的PHP已安装了`swoole`扩展
6. 进入目录, 输入```composer update```
7. 在网站环境中设置 `public/` 为运行目录并确保其中的 `images` 目录可写
8. 访问 `https://api.telegram.org/bot<bot_token>/setWebHook?url=https://<Your_URL>/webhook.php` 设置WebHook, 若认为不安全, 可自行改变文件名
9. 配置进程守护程序(**强烈建议**):

    - Systemd
        ```ini
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
10. 终端输入 ```service QQ2TG start```
11. enjoy it

- TG端发送消息:
    1. 私聊机器人并发送 `/new_chat`
    2. 选择要私聊的用户
    3. 回复机器人发出的消息开始私聊

- Web 消息查看:
    1. 在 Config.php 中设置好 `program -> password`
    2. 打开 `http(s)://<Your URL>/admin/message.html` 并将权限密钥填写完整
    3. enjoy it

## 配置安全 (**极为重要!**)

- Nginx:
    ```nginx
    location ~ (^\.|/\.) {
        return 403;
    }
    ```

- Apache:
    ```apacheconfig
    RewriteEngine On
    RewriteRule (^\.|/\.) - [F]
    ```

- IIS:
    ```xml
    <configuration>
      <system.webServer>
        <rewrite>
          <rules>
            <rule name="Block access to dotfiles">
              <match url="(^\.|/\.)" ignoreCase="false" />
              <action type="CustomResponse" statusCode="403" statusReason="Forbidden" />
            </rule>
          </rules>
        </rewrite>
      </system.webServer>
    </configuration>
    ```

## 问题

**本项目仅对 酷 Q Pro 版本提供支持, 对使用 Air 或 Lite 的用户不提供技术支持**

日志等级建议调至 2 或以上，否则可能会造成严重的消息延误

由于 Telegram 的特殊性，所有由 QQ 转发至 Telegram 的 GIF 图像都会以链接的方式发送，至于客户端能不能自动解析显示就要看运气了 /滑稽

若要在 Linux 上使用 酷Q , 可参考[这里](https://github.com/CoolQ/docker-wine-coolq)

## TODO

- ~~异步消息发送~~
- ~~图片信息缓存~~
- ~~用户群名片缓存~~
- ~~支持私聊消息~~
- ~~支持消息撤回~~
- 更新时自动更新配置
- ~~解决文档中提到的问题~~
  - ~~从 Telegram 端发起私聊~~
- ~~更多CQ码兼容~~

dalao 们如果有任何问题或者建议请在issue中提出或直接提交PR，感谢万分

## 题外话

毕竟初中生, 就是头脑简单四肢发达, 思维算法一坨屎, 英语极差, 导致代码里很多奇怪或者重复的变量名, 还请多多包涵,有些地方代码质量不高、效率低下, 或者结构有问题的, 还请 dalao 们多多指点

或许真的要像[LWL](https://lwl.moe)说的那样`变得更优秀`吧

## 更新日志

2018/08/11 完全重构配置调用方式

2018/08/10 支持位置信息发送与接收

2018/08/09 新增 Web 端消息查看

2018/08/08 新增 rich/sign CQ 码兼容

2018/07/27 新增日志记录

2018/07/26 支持 Sticker 本地处理转发

2018/07/22 支持 Telegram 端先发起私聊

2018/07/20 支持消息撤回

2018/07/18 回复消息提醒对方

2018/07/12 支持双向私聊消息

2018/07/11 更新名片获取机制, 添加伪消息回复支持

2018/07/05 添加图片信息缓存、消息异步发送、群名片缓存, 支持保存消息

2018/07/02 第一版，支持 QQ 与 Telegram 消息双向互通，支持图片、sticker 发送