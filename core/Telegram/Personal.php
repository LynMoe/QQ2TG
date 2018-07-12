<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-07-12
 * Time: 1:26 PM
 */

class Personal
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
                 * 单张图片
                 */
                $param['image'] = $param['image'][0];

                /**
                 * 从图片地址中获取QQ图片ID
                 */
                preg_match_all('/\-(.*)\/0/',$param['image']['media'],$id);
                $qq_file_id = explode('-',$id[1][0])[1];

                /**
                 * 若已缓存则替换图片地址为 Telegram File ID
                 */
                $param['image']['media'] = Storage::get_file_id($qq_file_id,$param['image']['media']);

                $message = [
                    'chat_id' => CONFIG['admin_id'],
                    'media' => $param['image'],
                ];

                /**
                 * DEBUG
                 */
                /*echo "单张图片:\n";
                var_dump($message);
                echo "\n";*/

                /**
                 * 发送消息
                 */
                self::curl([
                    'api' => 'sendPhoto',
                    'data' => $message,
                    'qq_message_id' => $data['message_id'],
                ]);
            } else {
                /**
                 * 若已缓存则替换图片地址为 Telegram File ID
                 */
                foreach ($param['image'] as $key => $item)
                {
                    preg_match_all('/\-(.*)\/0/',$item['media'],$id);
                    $qq_file_id = explode('-',$id[1][0])[1];

                    $param['image'][$key]['media'] = Storage::get_file_id($qq_file_id,$item['media']);
                }

                $message = [
                    'chat_id' => CONFIG['admin_id'],
                    'media' => $param['image'],
                ];

                /**
                 * DEBUG
                 */
                /*echo "多张图片:\n";
                var_dump($param);
                echo "\n";*/

                /**
                 * 发送消息
                 */
                self::curl([
                    'api' => 'sendMediaGroup',
                    'data' => $message,
                    'qq_message_id' => $data['message_id'],
                ]);
            }
        } else {
            /**
             * 直接发送消息
             */
            self::curl([
                'api' => 'sendMessage',
                'data' => [
                    'chat_id' => CONFIG['admin_id'],
                    'text' => $data['message'],
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ],
                'qq_message_id' => $data['message_id'],
            ]);
        }
    }

    private static function curl($data)
    {
        $host = '/bot' . CONFIG['bot_token'] . '/';

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
                /**
                 * DEBUG
                 */
                /*var_dump($data);*/

                $url = $host . 'sendMediaGroup?chat_id=' . $data['data']['chat_id'] . '&media=' . urlencode(json_encode($data['data']['media']));
                break;
        }

        /**
         * DEBUG
         */
        /**
         * 请求地址
         */
        echo "请求地址:\n";
        var_dump($url);
        echo "\n";

        $domainName = "api.telegram.org";

        /**
         * 新建 Swoole 异步HTTP客户端
         */
        $cli = new swoole_http_client($domainName, 443, true);

        /**
         * 设置代理
         */
        if (!empty(CONFIG['HTTP_proxy_host'])) $cli->set(['timeout' => CONFIG['http_timeout'],'http_proxy_host' => CONFIG['HTTP_proxy_host'],'http_proxy_port' => CONFIG['HTTP_proxy_port'],]); $cli->set(['timeout' => CONFIG['http_timeout'] + 15,]);

        /**
         * 设置请求头
         */
        $cli->setHeaders([
            'Host' => $domainName,
            "User-Agent" => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.79 Safari/537.36',
            'Accept' => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp',
            'Accept-Encoding' => 'Accept-Encoding: gzip, deflate, br',
        ]);

        /**
         * 发送异步请求
         */
        $cli->get($url, function ($cli) use ($data) {
            /**
             * DEBUG
             */
            echo "异步返回消息: \n" .  $cli->body . "\n";

            if (!isset(json_decode($cli->body,true)['result'])) return;
            $result = json_decode($cli->body,true)['result'];
            if (count($result) == 0) return null;

            /**
             * 将Telegram消息ID与数据绑定
             */
            if (!isset($result['message_id'])) $result['message_id'] = $result[0]['message_id'];
            Storage::bind_private_message($data['qq_message_id'],$result['message_id']);


            /**
             * 若有新图片则缓存 Telegram File ID
             */
            if (isset($result['photo']))
            { //单张图片
                $tg_file_id = $result['photo'][count($result['photo']) - 1]['file_id'];
                $qq_file_url = $data['data']['media']['media'];

                /**
                 * 检测发送的地址类型
                 */
                if (stripos($qq_file_url,'http') !== false)
                {
                    preg_match_all('/\-(.*)\/0/',$qq_file_url,$id);
                    $qq_file_id = explode('-',$id[1][0])[1];
                    /**
                     * 添加缓存
                     */
                    Storage::save_image_id($qq_file_id,$qq_file_url,$tg_file_id);
                }

            } elseif (isset($result[0]['message_id']))
            { //多张图片

                /**
                 * 对齐QQ图片 ID 和 Telegram File ID
                 */
                for ($i=0;$i<count($result);$i++)
                {
                    $images[] = [
                        'file_id' => $result[$i]['photo'][count($result[$i]['photo']) - 1]['file_id'],
                        'file_url' => $data['data']['media'][$i]['media'],
                    ];
                }

                foreach ($images as $item)
                {
                    /**
                     * 检测发送的地址类型
                     */
                    if (stripos($item['file_url'],'https') !== false)
                    {
                        preg_match_all('/\-(.*)\/0/',$item['file_url'],$id);
                        $qq_file_id = explode('-',$id[1][0])[1];
                        /**
                         * 添加缓存
                         */
                        Storage::save_image_id($qq_file_id,$item['file_url'],$item['file_id']);
                    }
                }
            }
        });
    }
}