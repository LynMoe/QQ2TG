<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 2:27 PM
 */

require_once __DIR__ . '/../Telegram/Group.php';
require_once __DIR__ . '/../Method.php';

class GroupMessage
{
    /**
     * 处理 CQ 码内容
     * @param $data
     */
    public static function handler($data)
    {
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

        $message = '<i> ' . Storage::get_card($data['user_id'],$data['group_id']) . " </i>[{$data['user_id']}]" . $header . $data['message'];

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
        Group::splice($param,$data);
    }
}