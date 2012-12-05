<?php

require_once 'HttpClient.class.php';

define("MIN_SLEEP_USEC", 5);
define("MAX_SLEEP_USEC", 15);
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
	return $arr[1];
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
	$url = "http://epub.cnki.net/KNS/request/SearchHandler.ashx?action=&NaviCode=A001_4&ua=1.25&PageName=ASP.brief_result_aspx&DbPrefix=CDMD&DbCatalog=%E4%B8%AD%E5%9B%BD%E4%BC%98%E7%A7%80%E5%8D%9A%E7%A1%95%E5%A3%AB%E5%AD%A6%E4%BD%8D%E8%AE%BA%E6%96%87%E5%85%A8%E6%96%87%E6%95%B0%E6%8D%AE%E5%BA%93&ConfigFile=CDMD.xml&db_opt=%u4E2D%u56FD%u4F18%u79C0%u535A%u7855%u58EB%u5B66%u4F4D%u8BBA%u6587%u5168%u6587%u6570%u636E%u5E93&db_value=%u4E2D%u56FD%u535A%u58EB%u5B66%u4F4D%u8BBA%u6587%u5168%u6587%u6570%u636E%u5E93%2C%u4E2D%u56FD%u4F18%u79C0%u7855%u58EB%u5B66%u4F4D%u8BBA%u6587%u5168%u6587%u6570%u636E%u5E93&year_from=1980&his=0&__=Mon%20Nov%2026%202012%2023%3A14%3A44%20GMT%2B0800%20(%E4%B8%AD%E5%9B%BD%E6%A0%87%E5%87%86%E6%97%B6%E9%97%B4)";
	
	if(!$code || strlen(trim($code))!=0)
	    $url = preg_replace("/NaviCode=(.*?)&/", "NaviCode=$code&", $url);
    else
	{
		echo "code is Empty\n";
		exit;
	}
	return $url;
}

function getIndexURL($fp)
{
	$url = "http://epub.cnki.net/KNS/brief/brief.aspx?curpage=2&RecordsPerPage=20&QueryID=52&ID=&turnpage=1&tpagemode=L&dbPrefix=CDMD&Fields=&DisplayMode=listmode&PageName=ASP.brief_result_aspx#J_ORDER";
	
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
	echo "validate page content, ";
	$error = preg_match("/验证码/", $content);
	$size = strlen($content)/1024;
	echo "size: $size(KB).";
	if($size<3)
	{
		echo "有可能被发现了，尝试重新连接\n";
		/*
		echo "请检查是否遇到了验证码，然后决定输入0继续，1停止\n";
		$stdin = fopen('php://stdin', 'r');
		fscanf($stdin, "%d\n", $number);
		fclose($stdin);
		if($number==1)
		{
			exit;
		}
		*/
		return false;
	}
	else
	{
		echo "...OK\n";
		return true;
	}
}

function parseContent($content, $fileName) 
{
	echo "parseContent.......";
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
	
	echo "Done!\n";
}

function fakeSleep()
{
	$ms = rand(MIN_SLEEP_USEC, MAX_SLEEP_USEC);
	echo "sleep $ms seconds";
	for($i=0; $i<$ms; $i++)
	{
		echo ".";
		sleep(1);
	}
	echo "wake up now!\n";
}

function main($subDir, $class, $cookieURL, $indexURL, $totalClass, $curClass) {

	$isSleep = true;
	makeDir("./html/$subDir/$class/");
	$dataFileName = "data/$subDir/$class.log";

	$httpClient = new HttpClient("epub.cnki.net");

	$httpClient->get($cookieURL);
	$cookies = $httpClient->getCookies();
	$httpClient->setCookies($cookies);
	
	$content = "";
	$indexFname = "./html/$subDir/$class/index.html";
	
	if(file_exists(iconv("utf-8","gb2312", $indexFname)))
	{
		$isSleep = false;
		$content = file_get_contents(iconv("utf-8","gb2312", $indexFname));
		echo "From cache get index.....\n";
	}
	else
	{
		$isSleep = true;
		$httpClient->get($indexURL);
		$content = $httpClient->getContent();
		save($indexFname, $content);//保存
		echo "save index file...\n";
	}
	
	/* 解析出一共有多少页面 */
	$pageCount = parsePageCount($content);

	$articleCount = 20 * $pageCount;//计算一共有多少篇文章,大于等于实际文章数目，不影响结果
	echo "total article is $articleCount\n";
	$pageCount = $articleCount / ARTICLE_PRE_PAGE;
	$pageCount = ceil($pageCount);//向上取整,不放过任何数据
		
	if($pageCount==0)
	$pageCount = 1;
	
	if($pageCount >50)
	{
		echo "page count is big than 50\n";
	}
	
	echo "total page of $class is : $pageCount...............$curClass of $totalClass\n";
	if($isSleep)
	{
		fakeSleep();
	}
	
	/* 抓取每一个页面并且保存下来，保存的同时进行解析 */
	for($i=1; $i<=$pageCount; $i++)
	{
		$content = NULL;
		$pageI = getPageI($indexURL, $i);//第i页的地址
		$htmlI = "./html/$subDir/$class/$i.html";
		
		if(!file_exists(iconv("utf-8","gb2312", $htmlI)))
		{
			$isSleep = true;
			$httpClient->setCookies($cookies);
			$httpClient->get($pageI);
			$content = $httpClient->getContent();
			save($htmlI, $content);
			echo "From newwork & save $i.html..........[$i of $pageCount]\n";
		}
		else//本地文件是存在的
		{
			$content = file_get_contents(iconv("utf-8","gb2312", $htmlI));
			$ok = validatePageContent($content); //是否出现了验证码
			if(!$ok)//是个验证码页
			{
				$i = $i-1;
				delFile($htmlI);
				dosleep(60*5);
			}
			else//正常的页面
			{
				$isSleep = false;
				echo "Find local file $htmlI & skip\n";
			}
			continue;
		}

		$logName = "./data/$subDir/$class.log";
		if(!validatePageContent($content))
		{
			$i = $i-1;
			dosleep(60*5);
			continue;
		}
		parseContent($content, $logName);
		if($i!=$pageCount && $isSleep)
			fakeSleep();//睡一阵子
		else
		{
			echo "+\n";
			echo "+\n";
			echo "+ $class done\n";
			echo "+\n";
			echo "+\n";

		}
	}
}

function cleanIndexURL($url)
{
	$len = explode("#", $url);
	$url = $len[0];
	return $url;
}
/*
function textFlash($str)
{
	$ums = 300000;
	$len = strlen($str);
	for($i=0; $i<$len; $i++)
	{
		echo $str[$i];
		usleep($ums);
	}
}
*/

function getTotalClass($fname)
{
	$fname = iconv("utf-8","gb2312", $fname);
	$fp = fopen($fname, "r");
	$line = 0;
	while(fgets($fp)) $line++;
	fclose($fp);

	return $line;
}

function dosleep($seconds)
{
	echo "Sleep $seconds seconds";
	for($i=0; $i<$seconds; $i++)
	{
		echo ".";
		sleep(1);
	}
	echo "wake up!\n";
}

function delFile($fileName)
{
	$file = iconv("utf-8","gb2312", $fileName);
	if(unlink($file))
	{
		echo "Delete file $fileName success!\n";
	}
	else
	{
		echo "Delete file $fileName failure!";
	}
}