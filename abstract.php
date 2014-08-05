<?php
require_once "./lib/function.php";



function get_ref($dbPrefix)
{
	$configFile = $dbPrefix;
	//dbPrefix, ConfigFile, t精确到毫秒
	//http://epub.cnki.net/kns/brief/brief.aspx?pagename=ASP.brief_result_aspx&dbPrefix=CDMD&dbCatalog=中国优秀博硕士学位论文全文数据库&ConfigFile=CDMD.xml&research=off&t=1406554949460&keyValue=&S=1&DisplayMode=listmode
	//$url = "http://epub.cnki.net/kns/brief/brief.aspx?pagename=ASP.brief_result_aspx&dbPrefix=CDMD&dbCatalog=%e4%b8%ad%e5%9b%bd%e4%bc%98%e7%a7%80%e5%8d%9a%e7%a1%95%e5%a3%ab%e5%ad%a6%e4%bd%8d%e8%ae%ba%e6%96%87%e5%85%a8%e6%96%87%e6%95%b0%e6%8d%ae%e5%ba%93&ConfigFile=CDMD.xml&research=off&t=1406554949460&keyValue=&S=1&DisplayMode=listmode";
	$t = time() . "111";
	$url = "http://epub.cnki.net/kns/brief/brief.aspx?pagename=ASP.brief_result_aspx&dbPrefix=" .$dbPrefix . "&dbCatalog=%e4%b8%ad%e5%9b%bd%e4%bc%98%e7%a7%80%e5%8d%9a%e7%a1%95%e5%a3%ab%e5%ad%a6%e4%bd%8d%e8%ae%ba%e6%96%87%e5%85%a8%e6%96%87%e6%95%b0%e6%8d%ae%e5%ba%93&ConfigFile=" . $configFile . ".xml&research=off&t=" . $t . "&keyValue=&S=1&DisplayMode=listmode";
	return $url;
}

function get_content_url($content)
{
	$match = array();
	$pattern = '/<a.*?href="(.*?)"/';
	preg_match($pattern, $content, $match);
	$url = $match[1];
	$url = str_replace("&amp;", "&", $url);
	return $url;
}

function get_key_words($content)
{
	$ptn = '/<span id="ChDivKeyWord".*?>(.*?)<\/span>/ims';
	$match = array();
	preg_match_all($ptn, $content, $match);
	
	$rtn = "";
	foreach($match[1] as $html)
	{
		$rtn .= trim(str_replace(PHP_EOL, '', strip_tags($html)));
	}
	return $rtn;
}

function get_paper_abs($content)
{
	$ptn = '/<span id="ChDivSummary" name="ChDivSummary">(.*?)<\/span>/';
	$match = array();
	preg_match($ptn, $content, $match);
	return $match[1];
}

function get_mentor($content)
{
	$ptn = '/<a class="KnowledgeNetLink".*?code=">(.*?)<\/a>/ims';
	$match = array();
	preg_match_all($ptn, $content, $match);
	
	$rtn = "";
	foreach($match[1] as $html)
	{
		$rtn .= trim(str_replace(PHP_EOL, '', strip_tags($html)));
	}
	
	return $rtn;
}

function get_major($content)
{
	$ptn = '/【作者基本信息】(.*?)<\/p>/ims';
	$match = array();
	preg_match_all($ptn, $content, $match);
	$rtn = "";
	foreach($match[1] as $html)
	{
		$rtn .= trim(str_replace(PHP_EOL, '', strip_tags($html)));
	}
	$major = explode("，", $rtn);
	$major = trim(str_replace(PHP_EOL, '', trim($major[1])));

	return $major;
}
?>

<?php 


$key = $argv[1];
if(!$key)
{
	echo "usage \$php abstract.php 'A', 'B' ...\n";
	exit;
}

$files = get_all_log_file("./data/$key/");

makeDir("./data/abstract/");//存放论文摘要,不会重复创建

makeDir("./data/abstract/$key");//’A' , 'B'...

foreach($files as $file)//每个文件的
{
	$fp = fopen($file, "r");
	$file = iconv("gb2312","utf-8", $file);

	$subdir = basename($file, ".log");
	$subdir = win_dir_format($subdir);
	$dataSavePath = "./data/abstract/$key/" . $subdir;
	makeDir($dataSavePath);
	
	makeDir($dataSavePath . "/tmp");
	
	$mapFile = $dataSavePath . "/paper_abstract_url.log";
	$icount = 1;
	while($line=readLine($fp))//每一行
	{
		$sleep = true;
		$arr  = explode("\t", $line);
		$u = $arr[6];
		$paperName = $arr[0];
		//$paperName = win_dir_format($paperName);

		$code = $arr[7];
		
		/*获取Referer头*/
		$dbCode = get_db_code($u);//CDFD
		$refUrl = get_ref($dbCode);


		$cachedHtml = $dataSavePath . "/tmp/$paperName.html";
		$absPath = $dataSavePath . "/" . $paperName . ".log";
		echo "Cached check $cachedHtml...";
		$content = "";
		$localedCachedHtml = iconv("utf-8", "gb2312", $cachedHtml);
		if(!file_exists($localedCachedHtml))
		{
			$sleep = true;
			echo "Miss!\n";
			$cookieURL = getCookieURL($code);
			$httpClient = new HttpClient("epub.cnki.net");
			
			/*获取并设置cookie*/
			$httpClient->get($cookieURL);
			$cookies = $httpClient->getCookies();
			while(!$cookies)
			{
				echo "Cookie是空的，睡眠30S\n";
				sleep(30);
				$cookies = $httpClient->getCookies();
			}
			$httpClient->setCookies($cookies);

			
			$httpClient->setReferer($refUrl);
			
			$contentUrl = "http://epub.cnki.net" . $u;

			$httpClient->get($contentUrl);
			$content = $httpClient->getContent();//302页面
			
			/*解析地址*/
			$contentUrl = get_content_url($content);
			$saveContent = $paperName . "\t" . $contentUrl . "\n";
			save($mapFile, $saveContent, "a+");
			//echo "save $saveContent\n";
			
			/*抓取论文摘要内容*/

			$content = $httpClient->quickGet($contentUrl);
			save($cachedHtml, $content);
		}
		else
		{
			$sleep = false;
			echo "Hit\n";
			//echo $localedCachedHtml . "\n";
			$content = file_get_contents($localedCachedHtml);
			// save("./tmp.txt", $content);
			// echo $cachedHtml . "\n";
		}
		
		$keyWords = get_key_words($content);
		$keyWords .= "#" . get_mentor($content) . "#" . get_major($content);
		$abs = get_paper_abs($content);
		
		save("$absPath", $keyWords . "\n" . $abs);

		if($sleep)
		{
			fakeSleep();
		}
	}
}

?>