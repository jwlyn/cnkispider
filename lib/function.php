<?php

require_once 'HttpClient.class.php';

define("MIN_SLEEP_USEC", 13);
define("MAX_SLEEP_USEC", 47);
define("ARTICLE_PRE_PAGE", 200);

function readLine($fp)
{
	$line = fgets($fp);
	$line = trim($line);
	return $line;
}

function getClassName($line)
{
	$arr = explode(" ", $line);
	return $arr[0];
}

function getClassCode($line)
{
	$arr = explode(" ", $line);
	return $arr[0];
}
/*
function getClass($fp)
{
	$class = readLine($fp);
	return $class;
}
*/
function getCookieURL($code)
{
	$url = "http://epub.cnki.net/KNS/request/SearchHandler.ashx?action=&NaviCode=A001_4&ua=1.25&PageName=ASP.brief_result_aspx&DbPrefix=CDMD&DbCatalog=%E4%B8%AD%E5%9B%BD%E4%BC%98%E7%A7%80%E5%8D%9A%E7%A1%95%E5%A3%AB%E5%AD%A6%E4%BD%8D%E8%AE%BA%E6%96%87%E5%85%A8%E6%96%87%E6%95%B0%E6%8D%AE%E5%BA%93&ConfigFile=CDMD.xml&db_opt=%u4E2D%u56FD%u4F18%u79C0%u535A%u7855%u58EB%u5B66%u4F4D%u8BBA%u6587%u5168%u6587%u6570%u636E%u5E93&db_value=%u4E2D%u56FD%u535A%u58EB%u5B66%u4F4D%u8BBA%u6587%u5168%u6587%u6570%u636E%u5E93%2C%u4E2D%u56FD%u4F18%u79C0%u7855%u58EB%u5B66%u4F4D%u8BBA%u6587%u5168%u6587%u6570%u636E%u5E93&year_from=1980&his=0&__=Mon%20Nov%2026%202012%2021%3A49%3A06%20GMT%2B0800%20(%E4%B8%AD%E5%9B%BD%E6%A0%87%E5%87%86%E6%97%B6%E9%97%B4)";
	
	$url = preg_replace("/NaviCode=(.*?)&/", "NaviCode=$code&", $url);
	return $url;
}

function getIndexURL($fp)
{
	$url = "http://epub.cnki.net/KNS/brief/brief.aspx?curpage=2&RecordsPerPage=20&QueryID=42&ID=&turnpage=1&tpagemode=L&dbPrefix=CDMD&Fields=&DisplayMode=listmode&PageName=ASP.brief_result_aspx#J_ORDER";
	
	//$url = preg_replace("/NaviCode=(.*?)&/", "NaviCode=$code&", $url);
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
	if (!$dir)
	return false;
	
	$dir = iconv("utf-8","gb2312", $dir);
	if (!file_exists($dir)) {
		return mkdir($dir);
	} else {
		return true;
	}
}

function save($file, $content, $mod="w+")
{
	if(!$content)
	return;
	$file = iconv("utf-8","gb2312", $file);
	$fp = fopen("$file", $mod);
	fwrite($fp, $content);
	fclose($fp);
}

function changeArticlePerPage($indexURL, $count)
{
	$pattern = '/RecordsPerPage=(\d+)/';
	$indexURL = preg_replace($pattern, "RecordsPerPage=$count", $indexURL);
	return $indexURL;
}

function getPageI($url, $i)
{
	$pattern = "/curpage=(\d+)/";
	$nextUrl = preg_replace($pattern, "curpage=$i", $url);
	$nextUrl = changeArticlePerPage($nextUrl, ARTICLE_PRE_PAGE);
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

function validatePageContent($content)
{
	echo "validate page content...\n";
	$error = preg_match("/验证码/", $content);
	$size = strlen($content)/1024;
	echo "file size is $size KB\n";
	if($error && $size<3)
	{
		echo "有可能被发现了，请等待一会儿再开始\n";
		echo "请检查是否遇到了验证码，然后决定输入0继续，1停止\n";
		$stdin = fopen('php://stdin', 'r');
		fscanf($stdin, "%d\n", $number);
		fclose($stdin);
		if($number==1)
		{
			exit;
		}
	}
}

function parseContent($content, $fileName) 
{
	validatePageContent($content);
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
	//if($pageCount > 50)
	{
		$articleCount = 20 * $pageCount;//计算一共有多少篇文章,大于等于实际文章书目，不影响结果
		echo "total article is $articleCount\n";
		$pageCount = $articleCount / ARTICLE_PRE_PAGE;
		$pageCount = ceil($pageCount);//向上取整,不放过任何数据
	}
	if($pageCount==0)
	$pageCount = 1;
	
	if($pageCount >50)
	{
		echo "page count is big than 50\n";
		exit;
	}
	
	echo "total page of $class is : $pageCount\n";
	fakeSleep();
	/* 抓取每一个页面并且保存下来，保存的同时进行解析 */
	for($i=1; $i<=$pageCount; $i++)
	{
		$content = NULL;
		echo "begin to get page $i of $pageCount...\n";
		$pageI = getPageI($indexURL, $i);//第i页的地址
		$htmlI = "./html/$class/$i.html";
		
		if(!file_exists($htmlI))
		{
			$httpClient->setCookies($cookies);
			$httpClient->get($pageI);
			$content = $httpClient->getContent();
			save($htmlI, $content);
			echo "get file from newwork & saved file $htmlI \n";
		}
		else
		{
			echo "get file $htmlI from cache\n";
			$content = file_get_contents($htmlIs);
		}

		$logName = "./data/$class.log";
		parseContent($content, $logName);
		if($i!=$pageCount)
			fakeSleep();//睡一阵子
	}
}

function cleanIndexURL($url)
{
	$len = explode("#", $url);
	$url = $len[0];
	return $url;
}
