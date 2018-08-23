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
require_once __DIR__ . '/../core/Method.php';

/**
 * 检测目录是否存在与读写权限
 */
if (!is_dir(CONFIG['image']['folder'])) {if (mkdir(CONFIG['image']['folder'])) Method::log(1,'创建图片目录成功'); Method::log(3,'创建图片目录失败');}
if (!is_writable(CONFIG['image']['folder'])) Method::log(3,'Master 快去检查一下图片储存目录的读写权限喵~ (' . CONFIG['image']['folder'] . ')');

/**
 * 获取TG回调消息
 */
$data = json_decode(file_get_contents("php://input"),true);
if (empty($data)) die;
Method::log(0,'WebHook Receive Data: ' . json_encode($data));

/**
 * 判断操作人是不是 Bot 管理员
 */
if (!((@$data['callback_query']['from']['id'] == CONFIG['admin']['chat_id']) || (@$data['message']['from']['id'] == CONFIG['admin']['chat_id'])))
{
    die;
}

/**
 * 撤回消息按钮处理
 */
if (isset($data['callback_query']['data']))
{
    $return = json_decode($data['callback_query']['data'],true);
    switch ($return['type'])
    {
        case 'recall':
            $qq_return = json_decode($raw = file_get_contents(CONFIG['coolq']['http_url'] . '/delete_msg?message_id=' . $return['msg_id']),true);

            /**
             * 判断是否为私聊消息
             */
            if ($data['callback_query']['message']['chat']['id'] == CONFIG['admin']['send_to'])
            {
                /**
                 * 更改消息内容
                 */
                Method::curl("https://api.telegram.org/bot" . CONFIG['bot']['message'] . "/editMessageText?chat_id={$data['callback_query']['message']['chat']['id']}&message_id={$data['callback_query']['message']['message_id']}&text=" . urlencode('🔵撤回状态未知(仍有两分钟限制)'));

                break;
            }
            /**
             * 判断撤回状态
             */
            if ($qq_return['retcode'] != 0)
            {
                /**
                 * 更改消息内容
                 */
                Method::curl("https://api.telegram.org/bot" . CONFIG['bot']['message'] . "/editMessageText?chat_id={$data['callback_query']['message']['chat']['id']}&message_id={$data['callback_query']['message']['message_id']}&text=" . urlencode('🚫消息未撤回(两分钟已过)'));

                break;
            }

            /**
             * 更改消息内容
             */
            Method::curl("https://api.telegram.org/bot" . CONFIG['bot']['message'] . "/editMessageText?chat_id={$data['callback_query']['message']['chat']['id']}&message_id={$data['callback_query']['message']['message_id']}&text=" . urlencode('🔙消息已撤回'));

            break;

        case 'new_chat':
            Method::add_placeholder($return['user_id'],$data['callback_query']['message']['message_id']);
            /**
             * 更改消息内容
             */
            Method::curl("https://api.telegram.org/bot" . CONFIG['bot']['message'] . "/editMessageText?chat_id={$data['callback_query']['message']['chat']['id']}&message_id={$data['callback_query']['message']['message_id']}&text=" . urlencode('📤请直接回复该消息发起私聊'));
            break;
    }
    die;
}

/**
 * 判断消息为群组消息或私聊消息
 */
switch ($data['message']['chat']['type'])
{
    case 'supergroup':
        goto group;
        break;

    case 'group':
        group:
        if ($data['message']['chat']['id'] == CONFIG['admin']['send_to']) goto personal;
        /**
         * 初始化参数
         */
        $chat_id = $data['message']['chat']['id'];
        $qq_group = 0;
        $message = [];
        $tg_message_id = $data['message']['message_id'];

        /**
         * 获取QQ群信息
         */
        foreach (CONFIG['group'] as $key => $value)
        {
            if ($value == $chat_id) $qq_group = $key;
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
        if (isset($data['message']['forward_from'])) $message[] = ['type' => 'forward_from_user','nickname' => $data['message']['forward_from']['first_name'] . ' ' . $data['message']['forward_from']['last_name'],];
        if (isset($data['message']['forward_from_chat'])) $message[] = ['type' => 'forward_from_channel','nickname' => $data['message']['forward_from_chat']['title'],];
        if (isset($data['message']['location'])) $message[] = ['type' => 'location','lat' => $data['message']['location']['latitude'],'lon' => $data['message']['location']['longitude'],];


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
                    /**
                     * 性能检测
                     */
                    $time[] = microtime(true) - $start_time;

                    /**
                     * 添加图片
                     */
                    Storage::save_telegram_image($item['file_id']);
                    $send_message .= '[CQ:image,file=' . CONFIG['image']['url'] . $item['file_id'] . '.png]';

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

                    $send_message = "[回复给[CQ:at,qq={$result['user_id']}]: " . mb_substr($result['message'],0,20,'UTF-8') . "]\n" . $send_message;
                    break;
                case 'forward_from_user':
                    $send_message = "[转发自用户 " . $item['nickname'] . ")]\n" . $send_message;
                    break;
                case 'forward_from_channel':
                    $send_message = "[转发自频道 " . $item['nickname'] . ")]\n" . $send_message;
                    break;
                case 'location':
                    $send_message = "[CQ:location,lat={$item['lat']},lon={$item['lon']},style=1,title=Location]";
                    break;
            }
        }

        /**
         * 发送消息
         */
        $qq_result = json_decode(file_get_contents(CONFIG['coolq']['http_url'] . '/send_group_msg?group_id=' . $qq_group . '&message=' . urlencode($send_message)),true);

        /**
         * Log
         */
        Method::log(0,'Request CoolQ: ' . CONFIG['coolq']['http_url'] . '/send_group_msg?group_id=' . $qq_group . '&message=' . urlencode($send_message));
        Method::log(0,'CoolQ Return: ' . json_encode($qq_result));

        /**
         * 性能检测
         */
        $time[] = microtime(true) - $start_time;

        /**
         * Telegram 撤回按钮
         */
        if ($qq_result['status'] == 'ok' || $qq_result['retcode'] == 0)
        {
            error_log('Telegram Result: ' . Method::curl("https://api.telegram.org/bot" .
                    CONFIG['bot']['message'] . "/sendMessage?chat_id={$chat_id}&reply_to_message_id={$tg_message_id}&text=" .
                    urlencode('☑消息已发送') . "&reply_markup=" . json_encode([
                        'inline_keyboard' => [[
                            [
                                'text' => '❌撤回',
                                'callback_data' => json_encode(['type'=>'recall','msg_id' => $qq_result['data']['message_id']]),
                            ],],],
                    ])));
        } else {
            error_log('Telegram Result: ' . Method::curl("https://api.telegram.org/bot" .
                    CONFIG['bot']['message'] . "/sendMessage?chat_id={$chat_id}&reply_to_message_id={$tg_message_id}&text=" .
                    urlencode('❌消息发送失败, 错误码 ' . $qq_result['retcode'])));
        }


        break;

    case 'private':
        personal:
        /**
         * 初始化参数
         */
        $message = [];

        if (!isset($data['message']['reply_to_message']['message_id']))
        {
            $friends = [];

            foreach (json_decode(file_get_contents(CONFIG['coolq']['http_url'] . '/_get_friend_list'),true)['data'] as $item)
            {
                foreach ($item['friends'] as $key => $value)
                {
                    $friends[] = [
                        'text' => $value['remark'],
                        'callback_data' => json_encode(['type'=>'new_chat','user_id'=>$value['user_id']]),
                    ];

                    //$friends[$value['user_id']] = $value['remark'];
                }
            }

            Method::curl("https://api.telegram.org/bot" . CONFIG['bot']['message'] . "/sendMessage?chat_id=" . CONFIG['admin']['chat_id'] . "&reply_to_message_id={$data['message']['message_id']}&text=" . urlencode('🙋好友列表') . "&reply_markup=" . json_encode([
                    'inline_keyboard' => [$friends],
                ]));

            die;
        }
        $tg_message_id = $data['message']['reply_to_message']['message_id'];
        $qq_user_id = Storage::get_qq_user_id($tg_message_id);
        if ($qq_user_id <= 100000) die;

        /**
         * 将消息类型与内容转换为数组
         */
        if (isset($data['message']['photo'])) $message[] = ['type' => 'photo','file_id' => $data['message']['photo'][count($data['message']['photo']) - 1]['file_id'],];
        if (isset($data['message']['caption'])) $message[] = ['type' => 'text','content' => $data['message']['caption'],];
        if (isset($data['message']['text'])) $message[] = ['type' => 'text','content' => $data['message']['text'],];
        if (isset($data['message']['sticker'])) $message[] = ['type' => 'photo','file_id' => $data['message']['sticker']['file_id'],'width' => $data['message']['sticker']['width'],];
        if (isset($data['message']['location'])) $message[] = ['type' => 'location','lat' => $data['message']['location']['latitude'],'lon' => $data['message']['location']['longitude'],];

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
                    /**
                     * 性能检测
                     */
                    $time[] = microtime(true) - $start_time;

                    /**
                     * 添加图片
                     */
                    Storage::save_telegram_image($item['file_id']);
                    $send_message .= '[CQ:image,file=' . CONFIG['image']['url'] . $item['file_id'] . '.png]';

                    /**
                     * 性能检测
                     */
                    $time[] = microtime(true) - $start_time;

                    break;
                case 'text':
                    $send_message .= $item['content'];
                    break;
                case 'location':
                    $send_message = "[CQ:location,lat={$item['lat']},lon={$item['lon']},style=1,title=Location]";
                    break;
            }
        }

        /**
         * 发送消息
         */
        $qq_result = json_decode(file_get_contents(CONFIG['coolq']['http_url'] . '/send_private_msg?user_id=' . $qq_user_id . '&message=' . urlencode($send_message)),true);

        /**
         * Log
         */
        Method::log(0,'Request CoolQ: ' . CONFIG['coolq']['http_url'] . '/send_private_msg?user_id=' . $qq_user_id . '&message=' . urlencode($send_message));
        Method::log(0,'CoolQ Return: ' . json_encode($qq_result));

        /**
         * 性能检测
         */
        $time[] = microtime(true) - $start_time;

        /**
         * Telegram 撤回按钮
         */
        if ($qq_result['status'] == 'ok' || $qq_result['retcode'] == 0)
        {
            error_log('Telegram Result: ' . Method::curl("https://api.telegram.org/bot" .
                    CONFIG['bot']['message'] . "/sendMessage?chat_id=" . CONFIG['admin']['send_to'] .
                    "&reply_to_message_id={$data['message']['message_id']}&text=" . urlencode('☑消息已发送') . "&reply_markup=" . json_encode([
                        'inline_keyboard' => [[
                            [
                                'text' => '❌撤回',
                                'callback_data' => json_encode(['type'=>'recall','msg_id' => $qq_result['data']['message_id']]),
                            ],],],
                    ])));
        } else {
            error_log('Telegram Result: ' . Method::curl("https://api.telegram.org/bot" .
                    CONFIG['bot']['message'] . "/sendMessage?chat_id=" . CONFIG['admin']['send_to'] .
                    "&reply_to_message_id={$data['message']['message_id']}&text=" . urlencode('❌消息发送失败, 错误码 ' . $qq_result['retcode'])));
        }

        break;
}

/**
 * 性能检测
 */
$time[] = microtime(true) - $start_time;
$p_data = '';
foreach ($time as $value)
{
    $p_data .= ' ' . $value;
}
/**
 * Log
 */
Method::log(0,'WebHook Performance data: ' . $p_data);