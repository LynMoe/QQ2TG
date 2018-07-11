<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-07-02
 * Time: 1:14 PM
 */

/**
 * 性能检测
 */
$start_time = microtime(true);
$time[] = 0;

require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../core/Storage.php';

/**
 * 获取TG回调消息并填入日志
 */
file_put_contents(__DIR__ . '/Logs/' . time() . '.json',json_encode($data = json_decode(file_get_contents("php://input"),true)));

if (empty($data)) die;

log_it(json_encode($data));

/**
 * 判断消息为群组消息或私聊消息
 */
switch ($data['message']['chat']['type'])
{
    case 'group':
        /**
         * 初始化参数
         */
        $chat_id = $data['message']['chat']['id'];
        $qq_group = 0;
        $message = [];

        /**
         * 获取QQ群信息
         */
        foreach (CONFIG['group_settings'] as $key => $value)
        {
            if ($value['chat_id'] === $chat_id) $qq_group = $key;
        }
        if ($qq_group === 0) die();

        /**
         * 将消息类型与内容转换为数组
         */
        if (isset($data['message']['photo'])) $message[] = ['type' => 'photo','file_id' => $data['message']['photo'][count($data['message']['photo']) - 1]['file_id'],];
        if (isset($data['message']['caption'])) $message[] = ['type' => 'text','content' => $data['message']['caption'],];
        if (isset($data['message']['text'])) $message[] = ['type' => 'text','content' => $data['message']['text'],];
        if (isset($data['message']['sticker'])) $message[] = ['type' => 'photo','file_id' => $data['message']['sticker']['file_id'],'width' => $data['message']['sticker']['width'],];

        /**
         * 性能检测
         */
        $time[] = microtime(true) - $start_time;

        /**
         * 拼接消息数组
         */
        $send_message = '';
        foreach ($message as $item)
        {
            switch ($item['type'])
            {
                case 'photo':
                    $photo_url = "https://api.telegram.org/file/bot" . CONFIG['bot_token'] . "/" . $file_name = json_decode(curl("https://api.telegram.org/bot" . CONFIG['bot_token'] . "/getFile?file_id=" . $item['file_id']),true)['result']['file_path'];
                    //file_put_contents(__DIR__ . '/Data/Photos/' . md5($photo_url) . '.jpg',$file_content = file_get_contents($photo_url)); //储存文件

                    /**
                     * 性能检测
                     */
                    $time[] = microtime(true) - $start_time;

                    $tmp = explode('.',$file_name);
                    if ($tmp[1] == 'jpg')
                    {
                        $send_message .= '[CQ:image,file=' . $photo_url . ']';
                    } else {
                        /**
                         * 若为其它类型，转化为PNG文件
                         */
                        $send_message .= '[CQ:image,file=https://' . CONFIG['cloudimage_token'] . '.cloudimg.io/width/' . $item['width'] . '/tjpg/' . $photo_url . ']';
                    }

                    /**
                     * 性能检测
                     */
                    $time[] = microtime(true) - $start_time;

                    break;
                case 'text':
                    $send_message .= $item['content'];
                    break;
            }
        }

        /**
         * 获取要回复的消息
         */
        if (isset($data['message']['reply_to_message'])) $param = '&reply_to_message_id=' . Storage::get_qq_message_id($data['message']['reply_to_message']['message_id']);

        /**
         * 发送消息
         */
        file_get_contents(CONFIG['CQ_HTTP_url'] . '/send_group_msg_async?group_id=' . $qq_group . '&message=' . urlencode($send_message));

        /**
         * 保存消息
         */
        //Storage::save_messages('3029196824',$qq_group,json_encode($send_message),time());

        /**
         * 性能检测
         */
        $time[] = microtime(true) - $start_time;

        break;

    case 'private': //TODO

        break;
}

/**
 * 调试用
 * 请求TG-API (使用代理)
 * @param $url
 * @return mixed
 */
function curl($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    if (!empty(CONFIG['HTTP_proxy_host'])) curl_setopt ($ch, CURLOPT_PROXY, CONFIG['HTTP_proxy_host'] . ':' . CONFIG['HTTP_proxy_port']);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    curl_setopt ($ch, CURLOPT_TIMEOUT, CONFIG['http_timeout']);

    $headers = array();
    $headers[] = "Connection: keep-alive";
    $headers[] = "Pragma: no-cache";
    $headers[] = "Cache-Control: no-cache";
    $headers[] = "Upgrade-Insecure-Requests: 1";
    $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.79 Safari/537.36";
    $headers[] = "Accept-Encoding: gzip, deflate, br";
    $headers[] = "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close ($ch);
    return $result;
}

log_it(json_encode($time));

/**
 * 调试用
 * @param $message
 */
function log_it($message)
{
    curl("https://api.telegram.org/bot" . CONFIG['bot_token'] . "/sendMessage?chat_id=" . CONFIG['admin_id'] . "&text=" . urlencode($message));
}
