<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 2:27 PM
 */

require_once __DIR__ . '/../Telegram/Personal.php';
require_once __DIR__ . '/../Method.php';

class PersonalMessage
{
    /**
     * 处理 CQ 码内容
     * @param $data
     */
    public static function handler($data)
    {
        if (in_array($data['user_id'],CONFIG['blocked']['qq'])) return;

        /**
         * 匹配所有CQ码
         */
        preg_match_all("/\[CQ(.*?)\]/",$data['message'],$cq_code);
        $cq_code = $cq_code[0];

        $result = Method::handle_cq_code($cq_code,$data['message'],$data);
        $data['message'] = $result['message'];

        $header = $result['header'];
        $param = $result['param'];

        /**
         * 拼接用户名、CQ码以及消息正文
         */
        if (!empty(($header)))
        {
            if (!empty($data['message']))
            {
                $header = ": \n" . $header . "\n";
            } else {
                $header = ": \n" . $header;
            }
        } else {
            if (!empty($data['message']))
            {
                $header = ": \n";
            } else {
                $header = '';
                $data['message'] = '';
            }
        }

        $message = '<b>{私聊}</b> <i> ' . Method::get_friend_name($data['user_id']) . " </i>[{$data['user_id']}]" . $header . $data['message'];

        /**
         * 为第一张图片添加标题
         */
        foreach ($param['image'] as $key => $value)
        {
            $param['image'][$key] = ['type' => 'photo','media' => $value['media'],'parse_mode' => 'HTML','caption' => $message];
            break;
        }

        $data['message'] = $message;

        /**
         * 拼接文字消息与图片(若存在)
         */
        Personal::splice($param,$data);
    }
}