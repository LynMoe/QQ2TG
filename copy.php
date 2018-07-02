<?php
$dir = "C:\\Users\\cglgl\\Desktop\\QQ2TG\\Data\\Photos\\";
$to_dir = "C:\\Users\\cglgl\\Desktop\\CQP-xiaoi\\data\\image\\";
$done = [];

while (true)
{

    $handler = opendir($dir);
    while (($filename = readdir($handler)) !== false) {
        if ($filename != "." && $filename != "..") {
            $files[] = $filename ;
        }
    }

    closedir($handler);

    foreach ($files as $value) {
        if (!in_array($value,$done))
        {
            $file_content = file_get_contents($dir . $value);
            file_put_contents($to_dir . $value,$file_content);
            $done[] = $value;
            echo $value."\n";
        }
    }

}
