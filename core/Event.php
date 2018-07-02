<?php

require_once __DIR__ . '/Event/PersonalMessage.php';
require_once __DIR__ . '/Event/GroupMessage.php';


class Event
{
    public static function handler($data)
    {
        /**
         * 判断消息类型[群组/私聊]
         */
        switch ($data['message_type'])
        {
            case 'group':
                foreach (CONFIG['group_settings'] as $key => $value)
                {
                    if ($key == $data['group_id'])
                    {
                        GroupMessage::handler($data);
                        continue 2;
                    }
                }
                echo "未设定群组 {$data['group_id']} 对应的 Telegram 群组";
                break;
            case 'private':
                PersonalMessage::handler($data); //TODO
                break;
        }
    }
}