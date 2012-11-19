<?php
 
require_once 'HttpClient.class.php';

define("MIN_SLEEP_USEC", 13);
define("MAX_SLEEP_USEC", 47);

function readLine($file)
{
	$fp = fopen("$file", "r");
	$line = fgets($fp);
	fclose($fp);
	
	return $line;
}

function getCookieURL()
{
	$url = readLine("./cookieURL.log");
	return $url;
}

function getIndexURL()
{
	$url = readLine("./indexURL.log");
	return $url;
}

function saveFile($fileName, $text) {
	if (!$fileName || !$text)
	return false;

	if (makeDir(dirname($fileName))) {
		if ($fp = fopen($fileName, "w")) {
			if (@fwrite($fp, $text)) {
				fclose($fp);
				return true;
			} else {
				fclose($fp);
				return false;
			}
		}
	}
	return false;
}

function makeDir($dir, $mode = "0777") {
	if (!dir)
	return false;

	if (!file_exists($dir)) {
		return mkdir($dir, $mode, true);
	} else {
		return true;
	}
}

function save($file, $content, $mod="w+")
{
	if(!$content)
	return;
	$fp = fopen("$file", $mod);
	fwrite($fp, $content);
	fclose($fp);
}

function getPageI($url, $i)
{
	$pattern = "/curpage=(\d+)/";
	$nextUrl = preg_replace($pattern, "curpage=$i", $url);
	return $nextUrl;
}

function parsePageCount($content)
{
	$pattern = '/<\/div>\d+\/(\d+)<a id="Page_prev"/';
	$match = array();
	preg_match($pattern, $content, $match);
	//var_dump($match);
	return $match[1];
}

function parseArticleName($content)//解析文章名字
{
    
	$pattern = "/ReplaceChar\('(.*?)'\)/";
	$match = array();
	preg_match_all($pattern, $content, $match);
	
	return $match[1];
}

function parseAuthor($content)//解析文章作者
{
	$pattern = '/target="knet">(.*?)<\/a>/';
	$match = array();
	preg_match_all($pattern, $content, $match);
	
	return $match[1];
}

function parseSchool($content)//解析学位授予单位
{
	$pattern = '/target="cdmdNavi">(.*?)<\/a>/';
	$match = array();
	preg_match_all($pattern, $content, $match);
	
	return $match[1];
}

function parseYear($content)//学位授予年份*
{
	$pattern = "/<td>\s*(\d+年)\s*<\/td>/is";
	//$pattern = iconv("utf-8","gb2312", $pattern);
	$match = array();
	preg_match_all($pattern, $content, $match);
	
	return $match[1];
}

function parseOrigin($content)//学位来源*
{
	$pattern = "/<td>\s*(.*?士)\s*<\/td>/";
	$match = array();
	preg_match_all($pattern, $content, $match);
	
	return $match[1];
}

function parseDownCount($content)//论文下载次数
{
	$pattern = '/<span class="downloadCount">(\d+)<\/span>/';
	$match = array();
	preg_match_all($pattern, $content, $match);
	
	return $match[1];
}

function parsePreviewURL($content)//预览地址，有目录epub.cnki.net/
{
	$pattern = '/<a target="online_open" href=\'(.*?)\'>/';
	$match = array();
	preg_match_all($pattern, $content, $match);
	
	return $match[1];
}
function parseContent($content, $fileName) 
{
	echo "parseContent...\n";
	/* 文章名字，作者，学位授予单位，来源数据库，学位授予年度，下载次数，预览地址 */
	$articleName = parseArticleName($content);
	$authors = parseAuthor($content);
	$schools = parseSchool($content);
	$origin = parseOrigin($content);
	$years = parseYear($content);
	$downCount = parseDownCount($content);
	$previewPage = parsePreviewURL($content);
	
	$saveContent = "";
	$len = count($articleName);
	for($i=0; $i<$len; $i++)
	{
	    $item = "{$articleName[$i]} {$authors[$i]} {$schools[$i]} {$origin[$i]} {$years[$i]} {$downCount[$i]} {$previewPage[$i]}";
		$saveContent .= "$item\n";
		
	}
	
	save($fileName, $saveContent, "a+");
	
	echo "parseContent done!\n";
}

function fakeSleep()
{
	$ms = rand(MIN_SLEEP_USEC, MAX_SLEEP_USEC);
	echo "sleep $ms seconds...";
	sleep($ms);
	echo "wake up now!\n";
}

function main($class, $cookieURL, $indexURL) {

	makeDir("./html/$class/");
	$dataFileName = "data/$class.log";
	
	$httpClient = new HttpClient("epub.cnki.net");
	//$httpClient->setDebug(true);

	$httpClient->get($cookieURL);
	$cookies = $httpClient->getCookies();
	$httpClient->setCookies($cookies);
	
	$httpClient->get($indexURL);
	$content = $httpClient->getContent();
	save("./html/$class/index.html", $content);//保存
	echo "save index file...\n";
	
	/* 解析出一共有多少页面 */
	$pageCount = parsePageCount($content);
	echo "total page of $class is : $pageCount\n";
	fakeSleep();
	/* 抓取每一个页面并且保存下来，保存的同时进行解析 */
	for($i=1; $i<=$pageCount; $i++)
	{
	    $content = NULL;
	    echo "begin to get page $i of $pageCount...\n";
		$pageI = getPageI($indexURL, $i);//第i页的地址
		$htmlI = "./html/$class/$i.html";
		$httpClient->setCookies($cookies);
		$httpClient->get($pageI);
		$content = $httpClient->getContent();
		save($htmlI, $content);
		echo "saved file $htmlI and parse content now...\n";
		
		$logName = "./data/$class.log";
		parseContent($content, $logName);
		
		fakeSleep();//假装睡一阵子
	}
}

