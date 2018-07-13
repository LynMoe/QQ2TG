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

require_once __DIR__ . '/../core/Storage.php';

/**
 * 获取TG回调消息
 */
$data = json_decode(file_get_contents("php://input"),true);

if (empty($data)) die;

error_log('Receive Data: ' . json_encode($data));

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
        if ($qq_group === 0) die;

        /**
         * 将消息类型与内容转换为数组
         */
        if (isset($data['message']['photo'])) $message[] = ['type' => 'photo','file_id' => $data['message']['photo'][count($data['message']['photo']) - 1]['file_id'],];
        if (isset($data['message']['caption'])) $message[] = ['type' => 'text','content' => $data['message']['caption'],];
        if (isset($data['message']['text'])) $message[] = ['type' => 'text','content' => $data['message']['text'],];
        if (isset($data['message']['sticker'])) $message[] = ['type' => 'photo','file_id' => $data['message']['sticker']['file_id'],'width' => $data['message']['sticker']['width'],];
        if (isset($data['message']['reply_to_message'])) $message[] = ['type' => 'reply','message_id' => $data['message']['reply_to_message']['message_id'],'tg_group_id' => $data['message']['reply_to_message']['chat']['id'],];
        if (isset($data['message']['forward_from'])) $message[] = ['type' => 'forward','username' => $data['message']['forward_from']['username'],'nickname' => $data['message']['forward_from']['first_name'] . ' ' . $data['message']['forward_from']['last_name'],];

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
                case 'reply':
                    $result = Storage::get_message_content($item['tg_group_id'],$item['message_id']);

                    preg_match_all("/\[CQ(.*?)\]/",$result['message'],$cq_code);
                    $cq_code = $cq_code[0];

                    foreach ($cq_code as $value)
                    {
                        $temp = explode(',',$value);
                        if (str_replace('[CQ:','',$temp[0]) != 'face')
                        {
                            $data['message'] = str_replace($value,'',$data['message']) . ' ';
                        } else {
                            $temp[1] = str_replace(']','',str_replace('id=','',$temp[1]));
                            $data['message'] = str_replace($value,Method::handle_emoji_cq_code($temp[1]),$data['message']) . ' ';
                        }
                        switch (str_replace('[CQ:','',$temp[0]))
                        {
                            case 'image':
                                $type = '图片';
                                break;
                            case 'at':
                                $type = '@' . Storage::get_card(str_replace('qq=','',str_replace(']','',$temp[1])),$qq_group);
                                break;
                            case 'share':
                                $type = '分享消息';
                                break;
                            default:
                                $type = '某卡片';
                                break;
                        }
                        $result['message'] = str_replace($value,'[' . $type . ']',$result['message']);
                    }

                    $send_message = "[回复给" . Storage::get_card($result['user_id'],$qq_group) . ": " . mb_substr($result['message'],0,20,'UTF-8') . "]\n" . $send_message;
                    break;
                case 'forward':
                    $send_message = "[转发自 " . $item['nickname'] . " (@" . $item['username'] . ")]\n" . $send_message;
            }
        }

        /**
         * 发送消息
         */
        file_get_contents(CONFIG['CQ_HTTP_url'] . '/send_group_msg?group_id=' . $qq_group . '&message=' . urlencode($send_message));

        /**
         * 性能检测
         */
        $time[] = microtime(true) - $start_time;

        break;

    case 'private': //TODO

        /**
         * 初始化参数
         */
        $message = [];

        if (!isset($data['message']['reply_to_message']['message_id'])) die;
        $tg_message_id = $data['message']['reply_to_message']['message_id'];
        $qq_user_id = Storage::get_qq_user_id($tg_message_id);

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
         * 发送消息
         */
        file_get_contents(CONFIG['CQ_HTTP_url'] . '/send_private_msg?user_id=' . $qq_user_id . '&message=' . urlencode($send_message));

        /**
         * 性能检测
         */
        $time[] = microtime(true) - $start_time;

        break;
}

/**
 * 请求TG-API
 * @param $url
 * @return mixed
 */
function curl($url)
{
    error_log('Request Data: ' . $url);
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