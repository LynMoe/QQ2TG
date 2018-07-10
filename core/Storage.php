<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-07-02
 * Time: 10:58 PM
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Config.php';

class Storage
{
    /**
     * 获取指定群中指定成员的群名片(带缓存)
     * @param $user_id
     * @param $qq_group_id
     * @return mixed
     */
    public static function get_card($user_id,$qq_group_id)
    {
        $db = new \Buki\Pdox(CONFIG['database']);
        /**
         * 获取群名片数据
         */
        $data = $db->table('user_info')->where('user_id',$user_id)->where('qq_group_id',$qq_group_id)->get();

        /**
         * 检测群名片是否存在
         */
        if (!is_object($data))
        {
            $db->table('user_info')->insert([
                'user_id' => $user_id,
                'qq_group_id' => $qq_group_id,
                'card' => json_encode($card = self::get_new_card($user_id,$qq_group_id)),
                'flush_time' => time(),
            ]);
            return $card;
        } else {
            /**
             * 检测群名片是否过期
             */
            if ($data->flush_time - time() > 3600*6)
            {
                $db->table('user_info')->where('user_id',$user_id)->where('qq_group_id',$qq_group_id)->update([
                    'card' => json_encode($card = self::get_new_card($user_id,$qq_group_id)),
                    'flush_time' => time(),
                ]);
                return $card;
            } else {
                /**
                 * 直接返回群名片
                 */
                return json_decode($data->card,true);
            }
        }
    }

    /**
     * 重新获取用户群名片
     * @param $user_id
     * @param $qq_group_id
     * @return mixed
     */
    private static function get_new_card($user_id,$qq_group_id)
    {
        $card = json_decode(file_get_contents("http://" . CONFIG['CQ_HTTP_url'] . "/get_group_member_info?group_id={$qq_group_id}&user_id={$user_id}"),true)['data'];
        if ($card['card'] == '')
        {
            $card = $card['nickname'];
        } else {
            $card = $card['card'];
        }
        return $card;
    }

    /**
     * 保存消息
     * @param $user_id
     * @param $qq_group_id
     * @param $qq_message_id
     * @param $tg_group_id
     * @param $message
     * @param $time
     * @return bool
     */
    public static function save_message($user_id,$qq_group_id,$qq_message_id,$tg_group_id,$message,$time)
    {
        if (!CONFIG['save_messages']) return null;

        date_default_timezone_set('Asia/Shanghai');
        $db = new \Buki\Pdox(CONFIG['database']);
        $db->query("CREATE TABLE if not exists messages_" . date('Ymd') . "(id int PRIMARY KEY AUTO_INCREMENT,user_id BIGINT,message longtext,qq_group_id BIGINT,qq_message_id int,tg_group_id bigint,tg_message_id int,time int NOT NULL);");
        $db->table('messages_' . date('Ymd'))->insert([
            'user_id' => $user_id,
            'qq_group_id' => $qq_group_id,
            'qq_message_id' => $qq_message_id,
            'tg_group_id' => $tg_group_id,
            'message' => json_encode($message),
            'time' => $time,
        ]);
        return true;
    }

    /**
     * 保存QQ图片所对应的Telegram File ID
     * @param $qq_image_id
     * @param $qq_image_url
     * @param $tg_file_id
     * @return bool
     */
    public static function save_image_id($qq_image_id,$qq_image_url,$tg_file_id)
    {
        $db = new \Buki\Pdox(CONFIG['database']);
        if (!is_object($result = $db->table('image_file_id')->where('qq_img_id',$qq_image_id)->get()))
        {
            $db->table('image_file_id')->insert([
                'qq_img_id' => $qq_image_id,
                'qq_img_url' => json_encode($qq_image_url),
                'tg_file_id' => json_encode($tg_file_id),
                'time' => time(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * 获取QQ图片所对应的Telegram File ID
     * @param $qq_image_id
     * @param $qq_image_url
     * @return mixed
     */
    public static function get_file_id($qq_image_id,$qq_image_url)
    {
        $db = new \Buki\Pdox(CONFIG['database']);
        if (!is_object($result = $db->table('image_file_id')->where('qq_img_id',$qq_image_id)->get()))
        {
            return $qq_image_url;
        } else {
            return json_decode($result->tg_file_id,true);
        }
    }

    /**
     * 将 Telegram Message ID 与 QQ Message ID 对应
     * @param $qq_message_id
     * @param $tg_group_id
     * @param $tg_message_id
     */
    public static function bind_message($qq_message_id,$tg_group_id,$tg_message_id)
    {
        date_default_timezone_set('Asia/Shanghai');
        $db = new \Buki\Pdox(CONFIG['database']);
        $db->table('messages_' . date('Ymd'))->where('qq_message_id',$qq_message_id)->where('tg_group_id',$tg_group_id)->update([
            'tg_message_id' => $tg_message_id,
        ]);
    }

    /**
     * 获取 Telegram Message ID 对应的 QQ Message ID
     * @param $tg_message_id
     * @return int
     */
    public static function get_qq_message_id($tg_message_id)
    {
        date_default_timezone_set('Asia/Shanghai');
        $db = new \Buki\Pdox(CONFIG['database']);
        return $db->table('messages_' . date('Ymd'))->where('tg_message_id',$tg_message_id)->select('qq_message_id')->get()->qq_message_id;
    }
}