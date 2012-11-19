<?php
require_once "lib/function.php";

$class = $argv[1];              //命令行获取学科分类
$indexURL = getIndexURL();      //首页地址
$cookieURL = getCookieURL();    //初始化cookie，防止被识破
if(!$class || !indexURL || !cookieURL)
{
	echo "Usage: \$php main.php <学科分类>\nOR forgot to update the cookieURL.log/indexURL.log?";
	exit;
}

main($class, $cookieURL, $indexURL);

/*
$url = "./html/基础科学-自然科学研究/1.html";
$url = iconv("UTF-8","gb2312",$url);
$content = file_get_contents($url);

$match = parseOrigin($content);

var_dump($match);
*/