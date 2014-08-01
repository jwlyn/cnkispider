<?php
/*
 * 1, 每个分类在50次抓取内完成................~/
 * 2, 乱序抓取
 × 3，解析手工抓取的页面......................~/
 * 4，遇到验证码停止..........................~/
 × 5，自动除去indexURL中的#号后面的内容.......~/
 */
require_once "lib/function.php";

makeDir("./data");
makeDir("./html");

$class = $argv[1];
if(!file_exists("./data/$class"))
	makeDir("./data/$class");
if(!file_exists("./html/$class"))
	makeDir("./html/$class");

if(!$class)
{
	echo "you have to specify a directory: 'A', 'B', 'C' ...\n";
	exit;
}

$indexFileName = "./index/$class/_result_$class.log";
$totalClass= getTotalClass($indexFileName);
$curClass = 1;
if(!file_exists($indexFileName))
{
	echo "file $indexFileName not exists \n";
	exit;
}

$fp = fopen($indexFileName, "r");

while($line=readLine($fp))
{
	if(strlen(trim($line))==0)
		continue;
	$className = getClassName($line);
	$code = getClassCode($line);
	if(!$code ||!$className)
	{
		echo "$className code empty\n";
		continue;
	}
	$indexURL = getIndexURL($code);       //首页地址
	$cookieURL = getCookieURL($code);    //初始化cookie，防止被识破
	
	$indexURL = cleanIndexURL($indexURL);

	//exit;
	if(!$className || !$indexURL || !$cookieURL)
	{
		echo "Usage: \$php main.php";
		exit;
	}

	main($class, $className, $cookieURL, $indexURL, $totalClass, $curClass++, $code);
    echo "+\n";
	echo "+\n";
	echo "+抓取$className 成功 ^_^\n";
	//textFlash("成功 ^_^\n");
	echo "+\n";
	echo "+\n";
	
}//end while
