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
     * 将表情 CQ 码转换为Emoji
     * @param $cq_code
     * @return string
     */
    public static function handle_emoji_cq_code($cq_code)
    {
        $emoji_list = '{"0":"😮","1":"😣","2":"😍","3":"😳","4":"😎","5":"😭","6":"☺","7":"😷","8":"😴","9":"😭","10":"😰","11":"😡","12":"😝","13":"😃","14":"🙂","15":"🙁","16":"🤓","18":"😤","19":"😨","20":"😏","21":"😊","22":"🙄","23":"😕","24":"🤤","25":"😪","26":"😨","27":"😓","28":"😬","29":"🤑","30":"👊","31":"😤","32":"🤔","33":"🤐","34":"😵","35":"😩","36":"👿","37":"💀","38":"🤕","39":"👋","50":"🙁","51":"🤓","53":"😤","54":"🤮","55":"😨","56":"😓","57":"😬","58":"🤑","73":"😏","74":"😊","75":"🙄","76":"😕","77":"🤤","78":"😪","79":"👊","80":"😤","81":"🤔","82":"🤐","83":"😵","84":"😩","85":"👿","86":"💀","87":"🤕","88":"👋","96":"😰","97":"😅","98":"🤥","99":"👏","100":"🤢","101":"😬","102":"😐","103":"😐","104":"😩","105":"😠","106":"😞","107":"😟","108":"😏","109":"😙","110":"😧","111":"🤠","172":"😜","173":"😭","174":"😶","175":"😉","176":"🤓","177":"😵","178":"😜","179":"💩","180":"😳","181":"🤓","182":"😂","183":"🤓","212":"😳"}';
        $emoji_list = json_decode($emoji_list,true);
        if (isset($emoji_list[$cq_code])) return $emoji_list[$cq_code]; else return "未知表情";
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
        $friends_list = json_decode(file_get_contents(CONFIG['CQ_HTTP_url'] . '/_get_friend_list'),true)['data'];

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
        return json_decode(file_get_contents(CONFIG['CQ_HTTP_url'] . '/get_stranger_info?user_id=' . $user_id),true)['data']['nickname'];
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
}