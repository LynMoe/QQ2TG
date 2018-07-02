# QQ2TG
帮助QQ与Telegram互联的小程序

第一次写文档，写的不好还请多多指教 )

## 灵感
最近新入手了一台万普拉斯，艹了几天之后发现除了屏幕外就是QQ最耗电了，几乎占了7%，再加上以前就有这个念头，又正好是暑假，就挖了这个无底洞

## 简述
这个程序主体使用`PHP`编写，依赖于拓展`swoole`，配合酷Q的[coolq-http-api](https://github.com/richardchien/coolq-http-api)插件使用

目前还不是十分完善，可能存在消息转发不及时(消息队列)、各种报错，还请各位dalao指点一二

## 使用
1. 将代码拖到本地
2. 将`config\Config.example.php`改名为`config\Config.php`并填写完整
    - `ws_host`/`ws_port` :  本地`websocket`的主机和端口
    - `CQ_HTTP_url` :  酷Q HTTP-API的HTTP服务器地址
    - `cloudimage_token` :  用于将`webp`格式的sicker转换为png的api token，地址[在此](https://www.cloudimage.io)，一定限度内免费
    - `bot_token` :  机器人token，Telegram `@BotFather`获取
    - `admin_id` ：  Telegram 管理员的`chat_id`，目前用于发送性能调试数据
    - `group_settings` :  配置QQ群组与Telegram群组的关系，按照示范添加
    - `database` :  数据库基本信息(预留空位，将来的消息队列可能使用)
    - `HTTP_proxy` :  HTTP代理，用于请求Telegram服务器
3. 安装酷Q(若要发送图片则要求安装Pro版本)、[coolq-http-api](https://github.com/richardchien/coolq-http-api)插件，并添加配置一下参数:
    - use_ws_reverse :  是否使用反向WS
    - ws_reverse_api_url/ws_reverse_event_url ：  反向服务器地址，对应2中的`ws_host`/`ws_port`
    - host/port/use_http :  HTTP服务器设置，对应操作2中的`CQ_HTTP_url`
    ```
    "use_ws_reverse": true,
    "ws_reverse_api_url": "ws://192.168.31.120:9501",
    "ws_reverse_event_url": "ws://192.168.31.120:9501",
    
    "host": "0.0.0.0",
    "port": 5700,
    "use_http": true,
    ```
4. 确保您本地配置好科学上网工具并填写好了`HTTP_proxy`(若不需要请留空)
5. 确保您的PHP已安装了`swoole`扩展
6. 进入目录，输入`php run.php`
7. enjoy it

## 问题
现在可能会出现消息到达不及时、卡线程等情况，应该会在以后的版本中进行修正

若要修改消息样式，可前往`core\Event\GroupMessage.php`自行修改

**有可能**会出现程序运行时意外退出，若出现请将控制台输出日志提交为Issue，如果不弃坑的话应该会帮助解答/修正的

QQ中的表情包(不包括漫游表情等图片表情)暂未找到方法获取，若找到方法会添加的

## TODO
- 多线程模式
- 图片信息缓存
- 用户群名片缓存
- 支持私聊消息
- 更多CQ码兼容

dalao们如果有任何问题或者建议请在Issue中提出或者提交PR，感谢万分

## 题外话
写这个程序感觉还是蛮艰辛的，虽然代码不算复杂，有些地方也效率低下，但感觉自己的水平还是在提升，也学到了一些东西，刚刚在群里看到xtl发了一张图片:
![xtl.jpg](https://i.loli.net/2018/07/02/5b3a37a9156d8.jpg)

看完感觉触动挺深的，照这样来说我还要比他大一岁，可肚子里的东西就不知道要少多少了，或许真的要像LWL说的那样`变得更优秀`吧


## 更新日志
2018/07/02 第一版，支持QQ与Telegram消息双向互通，支持图片、sticker发送