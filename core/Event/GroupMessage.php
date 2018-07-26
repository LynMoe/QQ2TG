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
        $code = [];

        /**
         * 处理CQ码
         */
        foreach ($cq_code as $item)
        {
            /**
             * 获取CQ码类型和参数
             */
            $temp = explode(',',$item);

            /**
             * 将表情CQ码替换为Emoji
             */
            if (str_replace('[CQ:','',$temp[0]) != 'face')
            {
                $data['message'] = str_replace($item,'',$data['message']) . ' ';
            } else {
                $temp[1] = str_replace(']','',str_replace('id=','',$temp[1]));
                $data['message'] = str_replace($item,Method::handle_emoji_cq_code($temp[1]),$data['message']) . ' ';
            }

            /**
             * 筛选信息
             */
            switch (str_replace('[CQ:','',$temp[0]))
            {
                /**
                 * 若要添加CQ码支持在此添加
                 */
                case 'image':
                    if (substr(str_replace('file=','',$temp[1]),-3,3) == 'gif')
                    {
                        $code[] = [
                            'type' => 'gif',
                            /**
                             * raw 为QQ图片 URL
                             */
                            'raw' => str_replace('url=','',str_replace(']','',$temp[2])),
                        ];
                    } else {
                        $code[] = [
                            'type' => 'image',
                            /**
                             * raw 为QQ图片 URL
                             */
                            'raw' => str_replace('url=','',str_replace(']','',$temp[2])),
                        ];
                    }
                    break;
                case 'at':
                    $code[] = [
                        'type' => 'at',
                        /**
                         * raw 为被 at 用户的QQ号码
                         */
                        'raw' => str_replace('qq=','',str_replace(']','',$temp[1])),
                    ];
                    break;
                case 'share':
                    $code[] = [
                        'type' => 'share',
                        /**
                         * raw 为分享的详细信息
                         */
                        'raw' => [
                            'title' => str_replace('title=','',$temp[3]),
                            'content' => str_replace('content=','',$temp[1]),
                            'image' => str_replace('image=','',$temp[2]),
                            'url' => str_replace('url=','',str_replace(']','',$temp[4])),
                        ],
                    ];
                    break;
            }
        }

        /**
         * DEBUG
         */
        /*echo "去除CQ码后的消息: \n";
        var_dump($data);*/
        /*echo "CQ码列表: \n";
        var_dump($code);*/

        /**
         * 拼接文字消息
         */
        self::splice($data,$code);
    }

    /**
     * 发送前拼接文字消息
     * @param $data
     * @param $code
     */
    private static function splice($data,$code)
    {
        $header = '';
        $param = [];
        $param['image'] = [];

        /**
         * 遍历CQ码数组
         */
        foreach ($code as $k=>$v)
        {
            switch ($v['type'])
            {
                case 'at':
                    /**
                     * 获取被@人群名片
                     */
                    $card = Storage::get_card($v['raw'],$data['group_id']);
                    $header .= "[@<a href=\"http://wpa.qq.com/msgrd?uin={$v['raw']}\">{$card}</a>]";
                    break;
                case 'image':
                    /**
                     * 格式化为 Telegram Bot API 支持的格式
                     */
                    $param['image'][] = [
                        'type' => 'photo',
                        'media' => $url = str_replace('https://gchat.qpic.cn',CONFIG['image_proxy'],$v['raw']),
                    ];
                    break;
                case 'gif':
                    $header .= "[GIF]<a href='{$v['raw']}'>链接</a>";
                    break;
                case 'share':
                    $header .= "[分享]<a href='{$v['raw']['url']}'>{$v['raw']['title']}</a>\n{$v['raw']['content']}\n<a href='{$v['raw']['url']}'>链接</a>\n<a href='{$v['raw']['image']}'>Media</a>";
                    break;
            }
        }

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
        $message = '<i> ' . Storage::get_card($data['user_id'],$data['group_id']) . " </i>[<a href=\"http://wpa.qq.com/msgrd?uin={$data['user_id']}\">{$data['user_id']}</a>]" . $header . $data['message'];

        /**
         * 为第一张图片添加标题
         */
        foreach ($param['image'] as $key => $value)
        {
            $param['image'][$key] = ['type' => 'photo','media' => $value['media'],'parse_mode' => 'HTML','caption' => $message];
            break;
        }

        /**
         * DEBUG
         */
        /*echo "消息内容:\n";
        var_dump($data['message'] = $message);
        echo "\n";*/

        $data['message'] = $message;

        /**
         * 拼接文字消息与图片(若存在)
         */
        Group::splice($param,$data);
    }
}