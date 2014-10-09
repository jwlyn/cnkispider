<?php

require_once 'HttpClient.class.php';

define("MIN_SLEEP_USEC", 4);
define("MAX_SLEEP_USEC", 7);
define("ARTICLE_PRE_PAGE", 50);

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
	if(count($arr)!=2)
	{
		return null;
	}
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
	$url = "http://epub.cnki.net/KNS/request/SearchHandler.ashx?action=&NaviCode=I&ua=1.25&PageName=ASP.brief_result_aspx&DbPrefix=CDMD&DbCatalog=%E4%B8%AD%E5%9B%BD%E4%BC%98%E7%A7%80%E5%8D%9A%E7%A1%95%E5%A3%AB%E5%AD%A6%E4%BD%8D%E8%AE%BA%E6%96%87%E5%85%A8%E6%96%87%E6%95%B0%E6%8D%AE%E5%BA%93&ConfigFile=CDMD.xml&db_opt=%E4%B8%AD%E5%9B%BD%E4%BC%98%E7%A7%80%E5%8D%9A%E7%A1%95%E5%A3%AB%E5%AD%A6%E4%BD%8D%E8%AE%BA%E6%96%87%E5%85%A8%E6%96%87%E6%95%B0%E6%8D%AE%E5%BA%93&db_value=%E4%B8%AD%E5%9B%BD%E5%8D%9A%E5%A3%AB%E5%AD%A6%E4%BD%8D%E8%AE%BA%E6%96%87%E5%85%A8%E6%96%87%E6%95%B0%E6%8D%AE%E5%BA%93%2C%E4%B8%AD%E5%9B%BD%E4%BC%98%E7%A7%80%E7%A1%95%E5%A3%AB%E5%AD%A6%E4%BD%8D%E8%AE%BA%E6%96%87%E5%85%A8%E6%96%87%E6%95%B0%E6%8D%AE%E5%BA%93&his=0&__=Wed%20Oct%2008%202014%2009%3A34%3A39%20GMT%2B0800%20(%E4%B8%AD%E5%9B%BD%E6%A0%87%E5%87%86%E6%97%B6%E9%97%B4)";
	
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
	$url = "http://epub.cnki.net/kns/brief/brief.aspx?curpage=1&RecordsPerPage=".ARTICLE_PRE_PAGE."&QueryID=81&ID=&turnpage=1&tpagemode=L&dbPrefix=CDMD&Fields=&DisplayMode=listmode&PageName=ASP.brief_result_aspx";

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
		echo $dir . "\n";
		return @mkdir($dir);
	} else {
		return true;
	}
}

function save($file, $content, $mod="w+")
{
	if(!$content)
	return;
	
	//echo "SAVE FILE $file\n";
	$file = iconv("utf-8","gb2312//IGNORE", $file);
	$fp = fopen($file, $mod);
	if($fp)
	{
		fwrite($fp, $content);
		fclose($fp);
	}
	else{
		save("./tmp", $file . "\n", "a+");
	}
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
	//$pattern = '/<\/div>\d+\/(\d+)<a id="Page_prev"/';
	$pattern = '/<span class=\'countPageMark\'>浏览\d+\/(\d+)<\/span>/';
	$pattern2 = '/<div.*?>.*?&nbsp;找到&nbsp;(\d+)&nbsp;条结果&nbsp;<\/div>/';
	
	$match = array();
	if(preg_match($pattern, $content, $match))
	{
		return $match[1];
	}
	else if(preg_match($pattern2, $content, $match))
	{
		return ceil($match[1]/ARTICLE_PRE_PAGE);
	}
	return 0;
}

function parseArticleName($content)//解析文章名字
{
	$pattern = "/ReplaceChar\('(.*?)'\)/";
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function parseAuthor($content)//解析文章作者
{
	$pattern = '/target="knet">(.*?)<\/a>/';
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function parseSchool($content)//解析学位授予单位
{
	$pattern = '/target="cdmdNavi">(.*?)<\/a>/';
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function parseYear($content)//学位授予年份*
{
	$pattern = "/<td>\s*(\d+年)\s*<\/td>/ism";
	//$pattern = iconv("gb2312", "utf-8", $pattern);
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function parseOrigin($content)//学位来源*
{
	$pattern = "/<td>\s*([博|硕]士)\s*<\/td>/ism";
	$pattern = "/\s*(博士|硕士)\s*/";
	//$pattern = iconv("gb2312", "utf-8", $pattern);
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function parseDownCount($content)//论文下载次数
{
	$pattern = '/<span class="downloadCount">(\d+)<\/span>/';
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function parsePreviewURL($content)//预览地址，有目录epub.cnki.net/
{
	$pattern = '/<a target="online_open" .*? href=\'(.*?)\'>/';
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function parseAbstractUrl($content)
{
	//$pattern = '/<a target="online_open" .*? href=\'(.*?)\'>/';
	$pattern = '/<a.*?class="fz14" href=\'(.*?)\' target=\'_blank\'><script .*?<\/a>/';
	$match = array();
	$rt = preg_match_all($pattern, $content, $match);
	if(!$rt)return array();
	return $match[1];
}

function validatePageContent($content)
{
	echo "validate page content\n";
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

function parseContent($content, $fileName, $code) 
{
	//save("./tmp.html", $content);
	echo "parseContent : $fileName >> ";
	/* 文章名字，作者，学位授予单位，来源数据库，学位授予年度，下载次数，预览地址 */
	$articleName = parseArticleName($content);
	$authors = parseAuthor($content);
	$schools = parseSchool($content);
	$origin = parseOrigin($content);
	$years = parseYear($content);
	//var_dump($origin);exit;
	//$downCount = parseDownCount($content);
	$previewPage = parsePreviewURL($content);
	$abstractUrl = parseAbstractUrl($content);
	//echo count($articleName) . " >> " . count($authors) . " >> " .count($schools) . " >> " .count($origin) . " >> " .count($years) . " \n";
	$saveContent = "";
	$len = count($articleName);
	for($i=0; $i<$len; $i++)
	{
		$articleNm = win_dir_format($articleName[$i]); 
		$item = "{$articleNm}\t{$authors[$i]}\t{$schools[$i]}\t{$origin[$i]}\t{$years[$i]}\t{$previewPage[$i]}\t{$abstractUrl[$i]}\t{$code}";
		$saveContent .= "$item\n";
	}
	
	if($len==0)
	{
		echo "Done... but get nothing form $fileName\n";
		return;
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

function fastSleep()
{
	$ms = rand(0, 2);
	sleep($ms);
}

function main($subDir, $class, $cookieURL, $indexURL, $totalClass, $curClass, $code) {

	$isSleep = true;
	makeDir("./html/$subDir/$class/");
	$dataFileName = "data/$subDir/$class.log";

	$httpClient = new HttpClient("epub.cnki.net");
	
	$content = "";
	$indexFname = "./html/$subDir/$class/index.html";
	
	$tf = iconv("utf-8","gb2312", $indexFname);
	$cookies = "";
	if(file_exists($tf))
	{
		$isSleep = false;
		$content = file_get_contents($tf);
		echo "From cache get index.....\n";
	}
	else
	{
		/*获取并设置cookie*/
		$httpClient->get($cookieURL);
		$cookies = $httpClient->getCookies();
		$httpClient->setCookies($cookies);
		if(!$cookies)die("cookie error");

		$isSleep = true;

		$httpClient->get($indexURL);
		$content = $httpClient->getContent();
		save($indexFname, $content);//保存
		echo "save index file...\n";
	}
	
	/* 解析出一共有多少页面 */
	
	$pageCount = parsePageCount($content);
	echo "Page is $pageCount ****\n";
	$articleCount = ARTICLE_PRE_PAGE * $pageCount;//计算一共有多少篇文章,大于等于实际文章数目，不影响结果
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
		
		if(!file_exists(iconv("utf-8", "gb2312", $htmlI)))
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
			$tmpf2 = iconv("utf-8","gb2312", $htmlI);
			$content = file_get_contents($tmpf2);
			$ok = validatePageContent($content); //是否出现了验证码
			if(!$ok)//是个验证码页
			{
				$i = $i-1;
				delFile($htmlI);
			}
			else//正常的页面
			{
				$isSleep = false;
				echo "Find local file $htmlI & skip\n";
			}
			//continue;
		}

		$logName = "./data/$subDir/$class.log";
		if(!validatePageContent($content))
		{
			$i = $i-1;
			delFile($htmlI);
			dosleep(60);
			$httpClient = new HttpClient("epub.cnki.net");
			$httpClient->get($cookieURL);
			$cookies = $httpClient->getCookies();
			$httpClient->setCookies($cookies);
			continue;
		}
		parseContent($content, $logName, $code);
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
	$fname1 = iconv("utf-8","gb2312", $fname);
	$fp = fopen($fname1, "r");
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
	if(file_exists($file) && unlink($file))
	{
		echo "Delete file $fileName success!\n";
	}
	else
	{
		echo "Delete file $fileName failure!\n";
	}
}


function get_db_code($url)
{
	$match = array();
	preg_match("/dbcode=(.*?)&/", $url, $match);

	return $match[1];
	
}

function get_file_name($url)
{
	$match = array();
	preg_match("/filename=(.*?)$/", $url, $match);
	return $match[1];
}

function get_table_name($url)
{
	$match = array();
	preg_match("/dbname=(.*?)&/", $url, $match);
	return $match[1];
}

function get_real_url($dbCode, $fileName, $tableName)
{
	$url = "http://kreader.cnki.net/Kreader/buildTree.aspx?dbCode=CDMD&FileName=2007097337.nh&TableName=CDFD9908&sourceCode=GZKJU&date=&year=2007&period=03&fileNameList=&compose=&subscribe=&titleName=&columnCode=&previousType=_&uid=";
	$dbCodePattern = "/dbCode=(.*?)&/";
	$fileNamePattern = "/FileName=(.*?)&/";
	$tableNamePattern = "/TableName=(.*?)&/";
	
	$url = preg_replace($dbCodePattern, "dbCode=".$dbCode."&", $url);
	$url = preg_replace($fileNamePattern, "FileName=".$fileName."&", $url);
	$url = preg_replace($tableNamePattern, "TableName=".$tableName."&", $url);
	
	return $url;
}


function get_all_log_file($path)
{
	$i = 1;
	$files = array();
	$dir = opendir($path);

	//列出 images 目录中的文件
	while (($file = readdir($dir)) !== false)
	{
		if(is_file($path . $file))
		{
			$files[] = $path . $file;
			//echo "[$i] >> Find file $file\n";
		}
		else
		{
			echo "$file not a file, skip\n";
		}
	}
	closedir($dir);
	
	return $files;
}

function win_dir_format($path)
{
	$path = str_replace("<", "_", $path);
	$path = str_replace(">", "_", $path);
	$path = str_replace("\\", "_", $path);
	$path = str_replace("|", "_", $path);
	$path = str_replace(":", "_", $path);
	$path = str_replace("\"", "_", $path);
	$path = str_replace("*", "_", $path);
	$path = str_replace("?", "_", $path);
	$path = str_replace(".", "_", $path);

	$path = str_replace("/", "_", $path);
	$path = str_replace("-","_", $path);
	$path = str_replace("—","_", $path);
	if(strlen($path)>100)
	{
		$path = iconv("utf-8", "gb2312", $path);
		$path = mb_substr($path, 0, 100);
		$path = iconv("gb2312", "utf-8", $path);
	}
	return $path;
}