<?php
/*
 * 1, 每个分类在50次抓取内完成................~/
 * 2, 乱序抓取
 × 3，解析手工抓取的页面
 * 4，遇到验证码停止..........................~/
 × 5，自动除去indexURL中的#号后面的内容.......~/
 */
require_once "lib/function.php";

makeDir("./data");
makeDir("./html");

$fpClass = fopen("./class.log", "r");
$fpIndexURL = fopen("./indexURL.log", "r");
$fpCookieURL = fopen("./cookieURL.log", "r");

if(!$fpClass || !$fpIndexURL || !$fpCookieURL)
{
	echo "fopen() error \n";
	exit;
}

while($class=getClass($fpClass))
{
	//$class = getClass($fpClass);              //命令行获取学科分类
	$indexURL = getIndexURL($fpIndexURL);      //首页地址
	$cookieURL = getCookieURL($fpCookieURL);    //初始化cookie，防止被识破

	$indexURL = cleanIndexURL($indexURL);

	if(!$class || !indexURL || !cookieURL)
	{
		echo "Usage: \$php main.php";
		exit;
	}

	main($class, $cookieURL, $indexURL);
	
	echo "**********************抓取$class 成功*************************\n";
	
}//end while

/*
$url = "./html/基础科学-自然科学研究/1.html";
$url = iconv("UTF-8","gb2312",$url);
$content = file_get_contents($url);

$match = parseOrigin($content);

var_dump($match);
*/