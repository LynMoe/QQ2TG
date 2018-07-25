<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-07-25
 * Time: 10:50 PM
 */
require_once __DIR__ . '/../config/Config.php';

if (isset($_GET['file_id']) && !!preg_match('/^[A-Za-z]+$/',$_GET['file_id']) && file_exists(CONFIG['image_folder'] . $_GET['file_id']))
{
    header('Content-Type: image/png');
    $img = imagecreatefrompng(CONFIG['image_folder'] . $_GET['file_id']);
    imagepng($img);
}