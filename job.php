<?php 
require_once "./lib/function.php";

function get_class_file($class)
{
	return "_result_$class.log";
}

function parse_classpath_code($line)
{
	$data = array();
	$arr = explode("\t", $line);
	$data['classpath'] = $arr[0];
	$data['code'] = $arr[1];
	
	$arr2 = explode("#", $arr[0]);
	$class = end($arr2);
	$data['class'] = $class;
	$data['parent_class'] = $arr2[count($arr2)-2];
	
	return $data;
}
function get_abstract_by_title($class, $classpath, $title)
{
	$abstract = array();
	
	$path = "./data/abstract/$class/$classpath/"."$title.log";
	$fp = fopen($path, "r+");
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
	
	return $abstract;
}

function get_title_detail_map($class, $classpath)
{
	$path = "./data/abstract/$class/$classpath/paper_abstract_url.log";
	$map = array();
	$fp = fopen($path, "r+");
	
	while($line=readLine($fp))
	{
		if(strlen($line)==0)continue;
		
		$arr = explode("\t", $line);
		$map[$arr[0]] = $arr[1];
	}
	
	return $map;
}

function get_index($class, $classpath, $title)
{
	$path = "./data/index/$class/$classpath/$title.html";
	$content = file_get_contents($path);
	return $content;
}

function get_index_url_map($class, $classpath)
{
	$path = "./data/index/$class/$classpath/paper_url_mapping.log";
	$map = array();
	$fp = fopen($path, "r+");
	
	while($line=readLine($fp))
	{
		if(strlen($line)==0)continue;
		
		$arr = explode("\t", $line);
		$map[$arr[0]] = $arr[1];
	}
	
	return $map;
}

function get_docs_by_classpath($class, $classpath, $baseInfo)
{
	$title_detail_url_map = get_title_detail_map($class, $classpath);
	$index_url_map = get_index_url_map($class, $classpath);
	$docs = array();
	$path = "./data/".$class;
	$file_list_of_class = $path . "/" . $classpath . ".log";
	$fp = fopen($file_list_of_class, "r+");
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
	}
	
	return $docs;
}

function save_2_mongo($docs)
{
	echo "save to mongodb ... Done\n";
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

$classFolder = "./index/";
$class = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
foreach($class as $c)
{
	$path = $classFolder . $c . "/" . get_class_file($c);//类 别入口
	$fp = fopen($path, "r+");
	while($line=readLine($fp))
	{
		$docInfo = parse_classpath_code($line);//class, classpath, code
		$classpath =  $docInfo['classpath'];
		$docInfo2 = get_docs_by_classpath($c, $classpath, $docInfo);
		save_2_mongo($docInfo2);
	}
}





?>