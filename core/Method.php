<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-07-12
 * Time: 1:47 PM
 */

require_once __DIR__ . '/../config/Config.php';

class Method
{
    /**
     * 将表情 CQ 码转换为 Emoji
     * @param $cq_code
     * @return string
     */
    public static function handle_emoji_cq_code($cq_code)
    {
        $emoji_list = '{"0":"😮","1":"😣","2":"😍","3":"😳","4":"😎","5":"😭","6":"☺","7":"😷","8":"😴","9":"😭","10":"😰","11":"😡","12":"😝","13":"😃","14":"🙂","15":"🙁","16":"🤓","18":"😤","19":"😨","20":"😏","21":"😊","22":"🙄","23":"😕","24":"🤤","25":"😪","26":"😨","27":"😓","28":"😬","29":"🤑","30":"👊","31":"😤","32":"🤔","33":"🤐","34":"😵","35":"😩","36":"👿","37":"💀","38":"🤕","39":"👋","50":"🙁","51":"🤓","53":"😤","54":"🤮","55":"😨","56":"😓","57":"😬","58":"🤑","73":"😏","74":"😊","75":"🙄","76":"😕","77":"🤤","78":"😪","79":"👊","80":"😤","81":"🤔","82":"🤐","83":"😵","84":"😩","85":"👿","86":"💀","87":"🤕","88":"👋","96":"😰","97":"😅","98":"🤥","99":"👏","100":"🤢","101":"😬","102":"😐","103":"😐","104":"😩","105":"😠","106":"😞","107":"😟","108":"😏","109":"😙","110":"😧","111":"🤠","172":"😜","173":"😭","174":"😶","175":"😉","176":"🤓","177":"😵","178":"😜","179":"💩","180":"😳","181":"🤓","182":"😂","183":"🤓","212":"😳"}';
        $emoji_list = json_decode($emoji_list,true);
        if (isset($emoji_list[$cq_code])) return $emoji_list[$cq_code]; else return "[未知表情]";
    }

    /**
     * 获取好友/陌生人昵称/备注
     * @param $user_id
     * @return string
     */
    public static function get_friend_name($user_id)
    {
        $db = new \Buki\Pdox(CONFIG['database']);
        $db->query("CREATE TABLE if not exists friends_info(id int PRIMARY KEY AUTO_INCREMENT,user_id bigint,remark text,flush_time int);");
        if (!is_object($result = $db->table('friends_info')->where('user_id',$user_id)->get()))
        {
            $db->table('friends_info')->insert([
                'user_id' => $user_id,
                'remark' => json_encode($remark = self::request_name($user_id)),
                'flush_time' => time(),
            ]);
            return $remark;
        } else {
            if ((time() - $result->flush_time) >= 3600*2)
            {
                $db->table('friends_info')->where('user_id',$user_id)->update([
                    'remark' => json_encode($remark = self::request_name($user_id)),
                    'flush_time' => time(),
                ]);
                return $remark;
            } else {
                return json_decode($result->remark,true);
            }
        }
    }

    /**
     * 请求 CoolQ API 获取昵称或备注
     * @param $user_id
     * @return string
     */
    public static function request_name($user_id)
    {
        $friends_list = json_decode(file_get_contents(CONFIG['coolq']['http_url'] . '/_get_friend_list'),true)['data'];

        foreach ($friends_list as $item)
        {
            foreach ($item['friends'] as $value)
            {
                if ($value['user_id'] == $user_id)
                {
                    return $value['remark'];
                }
            }
        }
        return json_decode(file_get_contents(CONFIG['coolq']['http_url'] . '/get_stranger_info?user_id=' . $user_id),true)['data']['nickname'];
    }

    /**
     * 插入发起私聊占位符
     * @param $user_id
     * @param $tg_message_id
     * @return bool
     */
    public static function add_placeholder($user_id,$tg_message_id)
    {
        $db = new \Buki\Pdox(CONFIG['database']);
        $db->table('private_messages')->insert([
            'user_id' => $user_id,
            'qq_message_id' => $tg_message_id,
            'content' => json_encode('TG私聊占位'),
            'tg_message_id' => $tg_message_id,
            'time' => time(),
        ]);
        return true;
    }

    /**
     * 请求TG-API
     * @param $url
     * @param $log
     * @return mixed
     */
    public static function curl($url,$log = true)
    {
        if ($log) self::log(0,'CURL Request URL: ' . $url);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        if (!empty(CONFIG['proxy']['host'])) curl_setopt ($ch, CURLOPT_PROXY, CONFIG['proxy']['host'] . ':' . CONFIG['proxy']['port']);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt ($ch, CURLOPT_TIMEOUT, CONFIG['program']['timeout']);

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

        if ($log) self::log(0,'CURL Return Data: ' . $result);

        return $result;
    }

    /**
     * 日志记录
     * @param $level
     * @param $message
     * @return null
     */
    public static function log($level,$message)
    {
        if (CONFIG['log']['level'] > $level) return null;
        switch ($level)
        {
            case 0:
                $level = 'DEBUG';
                break;
            case 1:
                $level = 'INFO';
                break;
            case 2:
                $level = 'NOTICE';
                break;
            case 3:
                $level = 'WARNING';
                break;
            case 4:
                $level = 'ERROR';
        }

        self::curl("https://api.telegram.org/bot" . CONFIG['bot']['debug'] . "/sendMessage?chat_id=" . CONFIG['admin']['send_to'] . "&text=" . urlencode("[{$level}]\n" . $message),false);
        return null;
    }

    public static function handle_cq_code($code_list,$message,$data)
    {
        $param['image'] = [];
        $header = '';

        foreach ($code_list as $value)
        {
            $cq_result = self::resolve_cq_code($value);

            /**
             * 将表情 CQ 码替换为 Emoji
             */
            if ($cq_result['type'] != 'face')
            {
                $message = str_replace($value,'',$message) . ' ';
            } else {
                $message = str_replace($value,Method::handle_emoji_cq_code($cq_result['data']['id']),$message) . ' ';
            }

            /**
             * 筛选信息
             */
            switch ($cq_result['type'])
            {
                /**
                 * 若要添加CQ码支持在此添加
                 */

                case 'at':
                    /**
                     * 判断被@人是否为 Master
                     */
                    if (MASTER_ID == $cq_result['data']['qq'])
                    {
                        $header .= "[@<a href=\"tg://user?id=" . CONFIG['admin']['chat_id'] . "\">您</a>]";
                        continue;
                    }

                    if ($cq_result['data']['qq'] == 'all')
                    {
                        $header .= "[@<a href=\"tg://user?id=" . CONFIG['admin']['chat_id'] . "\">全体成员</a>]";
                        continue;
                    }

                    /**
                     * 获取被@人群名片
                     */
                    $card = Storage::get_card($cq_result['data']['qq'],$data['group_id']);
                    $header .= "[@{$card}]";

                    break;

                case 'image':
                    if (substr($cq_result['data']['file'],-3,3) == 'gif')
                    {
                        $header .= "[GIF]<a href='{$cq_result['data']['url']}'>链接</a>";
                    } else {
                        $param['image'][] = [
                            'type' => 'photo',
                            'media' => $url = str_replace('https://gchat.qpic.cn',CONFIG['image']['proxy'],$cq_result['data']['url']),
                        ];
                    }

                    break;

                case 'share':
                    $header .= @"[分享]\n" .
                        "{$cq_result['data']['title']}\n" .
                        "{$cq_result['data']['content']}\n" .
                        "<a href='{$cq_result['data']['url']}'>链接</a>\n" .
                        "<a href='{$cq_result['data']['image']}'>Media</a>";

                    break;

                case 'sign':
                    $header .= @"[群签到]\n" .
                        "{$cq_result['data']['title']}\n" .
                        "<a href='{$cq_result['data']['image']}'>Media</a>";

                    break;

                case 'rich':
                    $header .= @"[富文本]\n" .
                        "{$cq_result['data']['text']}\n" .
                        "<a href='{$cq_result['data']['url']}'>链接</a>";

                    break;

                case 'location':
                    $param['location'] = [];
                    $param['location']['lat'] = $cq_result['data']['lat'];
                    $param['location']['lon'] = $cq_result['data']['lon'];

                    break;
            }
        }

        return [
            'header' => $header,
            'message' => $message,
            'param' => $param,
        ];
    }

    public static function resolve_cq_code($code)
    {
        /**
         * 获取CQ码类型和参数
         */
        $code = str_replace("\r",'',str_replace("\n",'',str_replace("\t",'',$code)));
        $code = substr($code,0,strlen($code) - 1);
        $code_data = explode(',',$code);

        $result = [];

        foreach ($code_data as $key => $value)
        {
            if ($key == 0)
            {
                $result['type'] = str_replace('[CQ:','',$code_data[0]);
                continue;
            }

            $temp = explode('=',$value);

            $result['data'][$temp[0]] = $temp[1];
        }

        return $result;
    }
}