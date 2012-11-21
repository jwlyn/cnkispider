<?php
/*
 * 1, 每个分类在50次抓取内完成................~/
 * 2, 乱序抓取
 × 3，解析手工抓取的页面
 * 4，遇到验证码停止..........................~/
 × 5，自动除去indexURL中的#号后面的内容.......~/
 */
require_once "lib/function.php";

$class = $argv[1];              //命令行获取学科分类
$indexURL = getIndexURL();      //首页地址
$cookieURL = getCookieURL();    //初始化cookie，防止被识破

$indexURL = cleanIndexURL($indexURL);

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