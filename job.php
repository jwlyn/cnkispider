<?php 
require_once "./lib/function.php";

function get_class_file($class)
{
	return "_result_$class.log";
}

function parse_classpath_code($line)
{
	$data = array();
	$arr = explode(" ", $line);
	$data['classpath'] = $arr[0];

	$data['code'] = $arr[1];
	
	$arr2 = explode("#", $arr[0]);
	$class = end($arr2);
	$data['class'] = $class;
	$data['parent_class'] = $arr2[count($arr2)-2];
	echo ".";
	return $data;
}
function get_abstract_by_title($class, $classpath, $title)
{
	$abstract = array();
	
	$path = "./data/abstract/$class/$classpath/"."$title.log";

	$p1 = iconv("utf-8", "gb2312//IGNORE", $path);
	if(!file_exists($p1))
	{
		save("logs/paper_not_found.txt", $path . "\n", "a+");
		echo $path . " NOT FOUND\n";
		return $abstract;
	}
	
	$fp = @fopen($p1, "r+");

	$i=0;
	while($line=readLine($fp))
	{
		if($i==0)
		{
			$i=1;
			$arr = explode("#", $line);
			
			$abstract['keyword'] = $arr[0];
			$abstract['mentor'] = $arr[1];
			$abstract['major'] = $arr[2];
		}
		else if($i==1)
		{
			$abstract['abstract_zh'] = $line;
			break;
		}
	}
	fclose($fp);
	return $abstract;
}

function get_title_detail_map($class, $classpath)
{
	$path = "./data/abstract/$class/$classpath/paper_abstract_url.log";
	$tmp = iconv("utf-8", "gb2312//IGNORE", $path);
	if(!file_exists($tmp))
	{
		echo $path . " NOT FOUND\n";
		save("logs/abs_url_not_found.txt", $path . "\n", "a+");
		return array();
	}
	
	$map = array();
	$fp = fopen(iconv("utf-8", "gb2312//IGNORE", $path), "r+");
	if(!$fp)die("$path not found");
	while($line=readLine($fp))
	{
		if(strlen($line)==0)continue;
		
		$arr = explode("\t", $line);
		$abstract_real_url = "";
		if(count($arr)==2)
		{
			$abstract_real_url = $arr[1];
		}
		$map[$arr[0]] = $abstract_real_url;
	}
	fclose($fp);
	return $map;
}

function get_index($class, $classpath, $title)
{
	$path = "./data/index/$class/$classpath/$title.html";
	$tpath = iconv("utf-8", "gb2312//IGNORE", $path);
	if(!file_exists($tpath))
	{
		save("logs/paper_index_not_found.txt", $path . "\n", "a+");
		return "";
	}
	$content = file_get_contents($tpath);
	if(strlen(trim($content))==0)die("$path content is empty");
	return $content;
}

function get_index_url_map($class, $classpath)
{
	$path = "./data/index/$class/$classpath/paper_url_mapping.log";
	echo $path . "\n";
	$map = array();
	$tmp = iconv("utf-8", "gb2312//IGNORE", $path);
	$fp = @fopen($tmp, "r+");
	if(!$fp)
	{
		echo $path . " NOT FOUND\n";
		save("logs/paper_index_url_not_found.txt", $path . "\n", "a+");
		return $map;
	}
	while($line=readLine($fp))
	{
		if(strlen($line)==0)continue;
		
		$arr = explode("\t", $line);
		$map[$arr[0]] = $arr[1];
	}
	fclose($fp);
	return $map;
}

function get_docs_by_classpath($class, $classpath, $baseInfo)
{
	$title_detail_url_map = get_title_detail_map($class, $classpath);
	$index_url_map = get_index_url_map($class, $classpath);
	$docs = array();
	$path = "./data/".$class;
	$file_list_of_class = $path . "/" . $classpath . ".log";
	
	$tmp = iconv("utf-8", "gb2312//IGNORE", $file_list_of_class);
	if(!file_exists($tmp))
	{
		echo $file_list_of_class . " NOT FOUND\n";
		
		save("logs/detail_not_found.txt", $path . "\n", "a+");
		return $docs;
	}
	
	$fp = fopen($tmp, "r+");
	
	while($line=readLine($fp))
	{
		if(strlen($line)==0)continue;
		
		$arr = explode("\t", $line);
		$doc = array();
		$doc = array_merge($doc, $baseInfo);
		$title = $doc['title'] = $arr[0];
		$doc['author'] = $arr[1];
		$doc['school'] = $arr[2];
		$doc['degree'] = $arr[3];
		$doc['year'] = $arr[4];
		$doc['read_url'] = $arr[5];
		$doc['abstract_302_url'] = $arr[6];
		$doc['code'] = $arr[7];
		
		$doc['abstract_url'] = $title_detail_url_map[$title];
		
		$doc = array_merge($doc, get_abstract_by_title($class, $classpath, $title));
		
		//$doc = array_merge($doc, get_index($class, $classpath, $title));
		$doc['index'] = get_index($class, $classpath, $title);
		$doc['index_url'] = $index_url_map[$title];
		$doc['ts'] = time();
		
		$docs[] = $doc;
	}
	fclose($fp);
	//exit;
	return $docs;
}

define("STANDARD_LEN", 19);
function save_2_mongo($docs)
{
	$len = count($docs)==0 ? 0 : count($docs[0]);
	if(count($docs)==0 || $len!=STANDARD_LEN)
	{
		echo "Empty Array\n";
	}
	else
	{
		echo "save to mongodb ... [$len]Done\n";
	}
	
}
?>

<?php 
/**
 * 接收参数，参数是一个数字，代表每次
 * 自动更新的数目
 */
$key = @$argv[1];
if(!$key)
{
	echo "Usage \$php job.php N(N is a number)";
	exit;
}
@mkDir("logs");
$classFolder = "./index/";
$class = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
$class = array("A");
foreach($class as $c)
{
	echo "process $c\n";
	$path = $classFolder . $c . "/" . get_class_file($c);//类 别入口
	$fp = fopen(iconv("utf-8", "gb2312//IGNORE", $path), "r+");
	while($line=readLine($fp))
	{
		$docInfo = parse_classpath_code($line);//class, classpath, code
		$classpath =  $docInfo['classpath'];
		$docInfo2 = get_docs_by_classpath($c, $classpath, $docInfo);
		save_2_mongo($docInfo2);
	}
	
	fclose($fp);
}





?>