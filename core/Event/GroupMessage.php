<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 2:27 PM
 */

require_once __DIR__ . '/../Telegram/Message.php';

class GroupMessage
{
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
            //TODO::将表情替换为Emoji
            if (str_replace('[CQ:','',$temp[0]) != 'face') $data['message'] = str_replace($item,'',$data['message']) . ' ';

            /**
             * 筛选信息
             */
            switch (str_replace('[CQ:','',$temp[0]))
            {
                case 'image':
                    $code[] = [
                        'type' => 'image',
                        'raw' => str_replace('url=','',str_replace(']','',$temp[2])),
                    ];
                    break;
                case 'at':
                    $code[] = [
                        'type' => 'at',
                        'raw' => str_replace('qq=','',str_replace(']','',$temp[1])),
                    ];
                    break;
                case 'share':
                    $code[] = [
                        'type' => 'share',
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

        echo "去除CQ码后的消息: \n";
        var_dump($data);

        echo "CQ码列表: \n";
        var_dump($code);

        /**
         * 拼接消息
         */
        self::splice($data,$code);
    }

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
                    $card = json_decode(self::curl("http://192.168.31.110:5700/get_group_member_info?group_id={$data['group_id']}&user_id={$v['raw']}"),true)['data'];
                    /**
                     * 获取被@人群名片
                     */
                    if ($card['card'] == '')
                    {
                        $card = $card['nickname'];
                    } else {
                        $card = $card['card'];
                    }
                    $header .= "[@{$card}]";
                    break;
                case 'image':
                    $param['image'][] = [
                        'type' => 'photo',
                        'media' => $v['raw'],
                    ];
                    break;
                case 'share':
                    $header .= "[分享]<a href='{$v['raw']['url']}'>{$v['raw']['title']}</a>\n{$v['raw']['content']}\n<a href='{$v['raw']['image']}'>Image</a>\n<a href='{$v['raw']['url']}'>Link</a>";
                    break;

            }
        }

        /**
         * 获取发送人群名片
         */
        $card = json_decode(self::curl("http://192.168.31.110:5700/get_group_member_info?group_id={$data['group_id']}&user_id={$data['user_id']}"),true)['data'];
        if ($card['card'] == '')
        {
            $card = $card['nickname'];
        } else {
            $card = $card['card'];
        }

        /**
         * 拼接用户名、CQ码以及消息正文
         */
        $message = '<b> ' . $card . " </b>:" . $header . "\n" . $data['message'];

        foreach ($param['image'] as $key => $value)
        {
            /**
             * 为第一张图片添加标题
             */
            $param['image'][$key] = ['type' => 'photo','media' => $value['media'],'parse_mode' => 'HTML','caption' => $message];
            break;
        }

        echo "消息内容:\n";
        var_dump($data['message'] = $message);
        echo "\n";

        /**
         * 拼接图片至消息中
         */
        Message::splice($param,$data);
    }

    private static function curl($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch,CURLOPT_TIMEOUT,3);

        $headers = array();
        $headers[] = "Connection: keep-alive";
        $headers[] = "Pragma: no-cache";
        $headers[] = "Cache-Control: no-cache";
        $headers[] = "Upgrade-Insecure-Requests: 1";
        $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.79 Safari/537.36";
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
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
}