<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-06-29
 * Time: 12:13 PM
 */

if (!file_exists(__DIR__ . '/../config/.env')) die('请先完成设置');
define('CONFIG',array_merge(parse_ini_file(__DIR__ . '/../config/.env.example',true),parse_ini_file(__DIR__ . '/../config/.env',true)));