<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-08-09
 * Time: 1:17 AM
 */
require_once __DIR__ . '/../core/Storage.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Method.php';

error_reporting(0);

if (!(isset($_GET['password']) && $_GET['password'] == CONFIG['web_password'])) die(json_encode(['status' => 403,'msg' => '无权操作']));

if (isset($_GET['user_id']) && isset($_GET['group_id']) && isset($_GET['time']) && isset($_GET['page']) && isset($_GET['limit']))
{
    $user_id = $_GET['user_id'];
    $group_id = $_GET['group_id'];
    $time = $_GET['time'];
    $page = $_GET['page'];
    $limit = $_GET['limit'];

    if ((empty($user_id) || !!preg_match('/^[0-9]*$/',$user_id)) && (empty($group_id) || !!preg_match('/^[0-9]*$/',$group_id)) && (!!preg_match('/^\d{10}$/',$time))
        && (!!preg_match('/^[0-9]*$/',$page) && $page > 0) && (!!preg_match('/^\d{1,3}$/',$limit)) && $limit > 1)
    {
        $query = '';
        if (!empty($user_id)) $query .= ' AND `user_id` = ' . $user_id . ' ';
        if (!empty($group_id)) $query .= ' AND `qq_group_id` = ' . $group_id . ' ';

        date_default_timezone_set('Asia/Shanghai');
        $db = new \Buki\Pdox(CONFIG['database']);

        $name = "COUNT(*)";
        $count = $db->query('SELECT COUNT(*) FROM messages_' . date('Ymd',$time) . ' WHERE time > ' . $time . $query)[0]->$name;

        $query_limit = 'LIMIT ' . ($limit * ($page - 1) + 1) . ',' . $limit;

        $query = 'SELECT id,user_id,qq_group_id,message,time FROM messages_' . date('Ymd',$time) . ' WHERE time > ' . $time . $query . ' ' . $query_limit;

        $data = json_decode(json_encode($db->query($query)),true);

        foreach ($data as $key => $value)
        {
            $data[$key]['message'] = json_decode($data[$key]['message'],true);
        }

        foreach ($data as $key => $value)
        {
            preg_match_all("/\[CQ(.*?)\]/",$data[$key]['message'],$cq_code);
            $cq_code = $cq_code[0];

            foreach ($cq_code as $item)
            {
                $result = Method::resolve_cq_code($item);

                switch ($result['type'])
                {
                    case 'image':
                        $result['data']['url'] = str_replace('https://gchat.qpic.cn',CONFIG['image_proxy'],$result['data']['url']);
                        $data[$key]['message'] = str_replace($item,'<img src="' . $result['data']['url'] . '">',$data[$key]['message']);
                        break;

                    case 'sign':
                        $data[$key]['message'] = str_replace($item,'[群签到]<br>' . $result['data']['title'] . '<br><img src="' . $result['data']['image'] . '">',$data[$key]['message']);
                        break;

                    case 'at':
                        $data[$key]['message'] = str_replace($item,'[@' . $result['data']['qq'] . ']',$data[$key]['message']);
                        break;

                    case 'face':
                        $data[$key]['message'] = str_replace($item,Method::handle_emoji_cq_code($result['data']['id']),$data[$key]['message']);
                        break;

                    case 'share':
                        $data[$key]['message'] = str_replace($item,'[分享]<br>' . @$result['data']['title'] . '<br>' . @$result['data']['content'] . '<br><img src="' . @$result['data']['image'] . '"<br>' . '<a href="' . @$result['data']['url'] . '">' . @$result['data']['url'] . '</a>',$data[$key]['message']);
                        break;
                }
            }
            //$data[$key]['message'] = urlencode($data[$key]['message']);
        }

        echo json_encode([
            'status' => 100,
            'data' => $data,
            'total_count' => $count,
        ]);
    } else {
        echo json_encode(['status' => 400,'msg' => '参数错误']);
    }
} else {
    echo json_encode(['status' => 400,'msg' => '缺少参数']);
}