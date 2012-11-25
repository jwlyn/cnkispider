<?php
/*
 $class = array(
 '基础科学'=>array(
     '生物'=>array(),
	 '物理'=>array(),
	 '农业'=>array(),
	 
 ),
 '太空科技'=>array(),
 
 );
 
 $code = array(
 '基础科学'=>'A',
 '生物'=>'A1',
 '物理'=>'A2'
 );
 */
require_once "./lib/function.php";
require_once './lib/HttpClient.class.php';

$className = array();
$classCode = array();


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
    $pattern = '/<input type="checkbox" id="selectbox" value="(.*?)".*?name="(.*?)" .*?>/';
	$pattern2 = '/<a.*?onclick="ClickNode\(\'(.*?)\',.*?>(.*?)<\/a>/';

	$content = @file_get_contents($url);
	$dir = get_code($url);
	$fileName = "./index/$dir/" . get_code($url) . ".html";
	save($fileName, $content);
	$match = array();
	$ret = preg_match_all($pattern, $content, $match);

	if(!$ret)/* 没有找到这个目录 */
	{
		$ret = preg_match_all($pattern2, $content, $match);
		if(!$ret)
		{
			echo "not found $url\n";
			save("./index/$dir/_$dir.log", $url ."\n".$content, "a+");
			return;
		}
	}
	$code = $match[1];
	$name = $match[2];
	for($i=0; $i<count($code); $i++)
	{
		$namei = $name[$i];
		$codei = $code[$i];
		var_dump($codei);
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
$urlsKey = array('A','B','C','D','E','F','G','H','I','J');
$urlsKey = array('A');
foreach($urlsKey as $key)
{
	mkdir("./index/$key");
	$url = replace_code($key);
	trivalIndex($url, $className);
	save("./index/$key/className.log", var_export($className, true));
	save("./index/$key/classCode.log", var_export($classCode, true));
	$arr = findKTreeLeaf($test);
	subStr1($arr);
	$ret = fullFillCode($arr);
	foreach($ret as $k=>$val)
	{
		save("./index/$key/_$key.log", "$k $val\n", "a+");
	}
}
