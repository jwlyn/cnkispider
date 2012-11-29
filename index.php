<?php
require_once "./lib/function.php";
//require_once './lib/HttpClient.class.php';

$className = array();
$classCode = array();
$cacheDir = "_";

function http_get_file($url)
{
    $httpClient = new HttpClient("epub.cnki.net");
	$httpClient->get($url);
	$content = $httpClient->getContent();
	return $content;
}

function addCode($name, $code)
{
    global $classCode;
	$classCode[$name] = $code;
}

 
function replace_code($code)
{
	//$url = "http://epub.cnki.net/KNS/request/NaviGroup.aspx?code=A&tpinavigroup=CDMDtpiresult&catalogName=&__=Thu Nov 22 2012 20:14:09 GMT+0800 (中国标准时间)";
	$url = "http://epub.cnki.net/KNS/request/NaviGroup.aspx?code=A&tpinavigroup=CDMDtpiresult&catalogName=&__=Thu%20Nov%2022%202012%2020:14:09%20GMT+0800%20(%E4%B8%AD%E5%9B%BD%E6%A0%87%E5%87%86%E6%97%B6%E9%97%B4)";
	$url = preg_replace("/code=(.*?)&/", "code=$code&", $url);
	
	return $url;
}

function get_code($url)
{
	$match = array();
	preg_match("/code=(.*?)&/", $url, $match);
	return $match[1];
}

function trivalIndex($url, &$className)
{
	global $cacheDir;
    $pattern2 = '/<input type="checkbox" id="selectbox" value="(.*?)".*?name="(.*?)" .*?>/';
	$pattern = '/<a.*?onclick="ClickNode\(\'(.*?)\',.*?>(.*?)<\/a>/';

	$dir = get_code($url);
	$fileName = "./index/$cacheDir/" . get_code($url) . ".html";
	$content = "";
	if(file_exists($fileName))
	{
		echo "get file $fileName from cache\n";
		$content = file_get_contents($fileName);
	}
	else
	{
		echo "get file $fileName from network\n";
		
		$content = @file_get_contents($url);
	    save($fileName, $content);
	}
	
	$match = array();
	$ret = preg_match_all($pattern, $content, $match);

	if(!$ret)/* 没有找到这个目录 */
	{
		$ret = preg_match_all($pattern2, $content, $match);
		echo "[WARNNING] use pattern 2\n $content\n";
		if(!$ret)
		{
			//echo "not found $url\n";
			save("./index/$cacheDir/{$cacheDir}.log", $url ."\n".$content."\n\n", "a+");
			return;
		}
	}
	$code = $match[1];
	$name = $match[2];
	for($i=0; $i<count($code); $i++)
	{
		$namei = $name[$i];
		$codei = $code[$i];
		//var_dump($codei);
		echo "\n$namei => $codei\n";
		addCode($namei, $codei);
		$className[$namei] = array();
		
		$url = replace_code($codei);
		trivalIndex($url, $className[$namei]);
		sleep(4);
	}
}

//返回一个array('xxx-yy-cc','aa-bb-cc')
function findKTreeLeaf($className)
{
	global $classCode;
	$ret = array();
	$j = 0;
	if(count($className)==0)
	{
		return array("");
	}
	foreach($className as $key=>$val)
	{
		if(is_array($className[$key]))
		{
			$arr = findKTreeLeaf($className[$key]);
			$len = count($arr);
			for($i=0; $i<$len; $i++)
			{
				$ret[$j++] = "$key-{$arr[$i]}";
			}
		}//if
	}//for
	
	return $ret;
}

function subStr1(&$arr)
{
	foreach($arr as &$val)
	{
		$val = substr($val, 0, strlen($val)-1);
	}
}

function fullFillCode($arr)
{
	global $classCode;
	$ret = array();
	foreach($arr as $val)
	{
		$tmpArr = explode("-", $val);
		$key = $tmpArr[count($tmpArr)-1];
		$code = $classCode[$key];
		$ret[$val] = $code;
	}
	
	return $ret;
}
////////////////////////////////////////////////////////////////////////////
$urlsKey = array('A'=>'基础科学','B'=>'工程科技Ⅰ辑','C'=>'工程科技Ⅱ辑','D'=>'农业科技','E'=>'医药卫生科技','F'=>'哲学与人文科学','G'=>'社会科学Ⅰ辑','H'=>'社会科学Ⅱ辑','I'=>'信息科技','J'=>'经济与管理科学');//解析全部目录

//$urlsKey = array('A'=>'基础科学');//只解析A目录
$key = $argv[1];
if(!$key)
{
	echo "usage \$php index.php 'A', 'B' ...\n";
	exit;
}
$urlsKey = array($key=>$urlsKey[$key]);
foreach($urlsKey as $key=>$value)
{
	global $className;
	$className[$value] = array();
	mkdir("./index/$key");
	global $cacheDir;
	$cacheDir = $key;
	$url = replace_code($key);
	trivalIndex($url, $className[$value]);
	save("./index/$key/className.log", var_export($className, true));
	save("./index/$key/classCode.log", var_export($classCode, true));

	$arr = findKTreeLeaf($className);
	subStr1($arr);
	$ret = fullFillCode($arr);
	foreach($ret as $k=>$val)
	{
		save("./index/$key/_result_$key.log", "$k $val\n", "a+");
	}
}
