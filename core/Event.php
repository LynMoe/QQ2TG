<?php

require_once __DIR__ . '/Event/PersonalMessage.php';
require_once __DIR__ . '/Event/GroupMessage.php';

class Event
{
    /**
     * 消息分类处理
     * @param $data
     */
    public static function handler($data)
    {
        /**
         * 判断消息类型[群组/私聊]
         */
        switch ($data['message_type'])
        {
            case 'group':
                foreach (CONFIG['group'] as $key => $value)
                {
                    if ($key == $data['group_id'])
                    {
                        /**
                         * 保存消息内容至数据库
                         */
                        Storage::save_message($data['user_id'],$data['group_id'],$data['message_id'],$value['chat_id'],$data['message'],$data['time']);

                        /**
                         * 发送至 /Event/GroupMessage.php handler 处理内容
                         */
                        GroupMessage::handler($data);

                        /**
                         * 跳出 if 与 switch
                         */
                        continue 2;
                    }
                }
                echo "未设定群组 {$data['group_id']} 对应的 Telegram 群组";
                break;
            case 'private':
                /**
                 * 保存私聊消息
                 */
                Storage::save_private_message($data['user_id'],$data['message_id'],$data['message'],$data['time']);

                /**
                 * 发送至 /Event/PrivateMessage.php handler 处理内容
                 */
                PersonalMessage::handler($data);
                break;
        }
    }
}