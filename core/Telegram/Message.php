<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 2:35 PM
 */

class Message
{
    public static function splice($param,$data)
    {
        /**
         * 判断是否有图片
         */
        if (count($param['image']) > 0)
        {
            if (count($param['image']) == 1)
            {
                /**
                 * 只有一张图片
                 */
                $param['image'] = $param['image'][0];
                $message = [
                    'chat_id' => CONFIG['group_settings'][$data['group_id']]['chat_id'],
                    'media' => $param['image'],
                ];

                echo "只有一张图片:\n";
                var_dump($message);
                echo "\n";

                /**
                 * 发送消息
                 */
                self::curl([
                    'api' => 'sendPhoto',
                    'data' => $message,
                ]);
            } else {
                $message = [
                    'chat_id' => CONFIG['group_settings'][$data['group_id']]['chat_id'],
                    'media' => $param['image'],
                ];

                echo "多张图片:\n";
                var_dump($param);
                echo "\n";

                /**
                 * 发送消息
                 */
                self::curl([
                    'api' => 'sendMediaGroup',
                    'data' => $message,
                ]);
            }
        } else {
            /**
             * 直接发送消息
             */
            self::curl([
                'api' => 'sendMessage',
                'data' => [
                    'chat_id' => CONFIG['group_settings'][$data['group_id']]['chat_id'],
                    'text' => $data['message'],
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ],
            ]);
        }
    }

    private static function curl($data)
    {
        $ch = curl_init();

        $host = 'https://api.telegram.org/bot' . CONFIG['bot_token'] . '/';

        /**
         * 判断消息类型
         */
        switch ($data['api'])
        {
            /**
             * 纯文本
             */
            case 'sendMessage':
                $url = $host . 'sendMessage?chat_id=' . $data['data']['chat_id'] . '&text=' . urlencode($data['data']['text']) . '&parse_mode=HTML&disable_web_page_preview=false';
                break;
            /**
             * 单张图片
             */
            case 'sendPhoto':
                $url = $host . 'sendPhoto?chat_id=' . $data['data']['chat_id'] . '&photo=' . urlencode($data['data']['media']['media']) . '&parse_mode=HTML&caption=' . urlencode($data['data']['media']['caption']);
                break;
            /**
             * 多张图片
             */
            case 'sendMediaGroup':
                var_dump($data);
                $url = $host . 'sendMediaGroup?chat_id=' . $data['data']['chat_id'] . '&media=' . urlencode(json_encode($data['data']['media']));
                break;
        }

        /**
         * 请求地址
         */
        echo "请求地址:\n";
        var_dump($url);
        echo "\n";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

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
        echo "请求返回:\n";
        var_dump($result);
        return $result;
    }
}