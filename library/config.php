<?php
//自动加载core里的class
define('CLASS_DIR', __DIR__);
// Add your class dir to include path
set_include_path(str_replace("\\","/",__DIR__));
//register autoloader
spl_autoload_extensions('.class.php');
spl_autoload_register();



//for debug output
function debug($str) {
    echo $str;
    echo "\n";
}

define('MEMCACHED',true);//设置为false则使用文件缓存,由于文件缓存不会过期,请每次下载前删除cache目录,防止缓存的url地址过期
define('MEMCACHED_HOST','127.0.0.1');
define('MEMCACHED_PORT',11211);

