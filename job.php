<?php 
require_once "./lib/function.php";
require_once "./lib/indexformat.php";

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

function trim_keyword($keyword)
{
	$arr = explode("；", $keyword);
	$str = "";
	foreach($arr as $k=>$val)
	{
		$val = trim($val);
		if(strlen($val)!=0)
			$str .= $val . ",";
	}
	return substr($str, 0, strlen($str)-1);
}

function get_abstract_by_title($class, $classpath, $title)
{
	$abstract = array();
	
	$path = "./data/abstract/$class/$classpath/"."$title.log";

	$p1 = iconv("utf-8", "gb2312//IGNORE", $path);
	if(!file_exists($p1))
	{
		save("logs/paper_not_found.txt", $path . "\n", "a+");
		echo $p1 . " NOT FOUND\n";
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
			
			$abstract['keyword'] = trim_keyword($arr[0]);
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
		echo $tmp . " NOT FOUND\n";
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
	$path = "./data/index/FIndex/$class/$classpath/$title.html";
	$tpath = iconv("utf-8", "gb2312//IGNORE", $path);
	if(!file_exists($tpath))
	{
		save("logs/paper_index_not_found.txt", $path . "\n", "a+");
		return "";
	}
	$content = file_get_contents($tpath);
	
	return $content;
}

function get_index_url_map($class, $classpath)
{
	$path = "./data/index/$class/$classpath/paper_url_mapping.log";
	echo iconv("utf-8", "gb2312//IGNORE", $path) . "\n";
	$map = array();
	$tmp = iconv("utf-8", "gb2312//IGNORE", $path);
	$fp = @fopen($tmp, "r+");
	if(!$fp)
	{
		echo $tmp . " NOT FOUND\n";
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
		$c = $doc['code'] = $arr[7];
		$doc['status'] = 0;
		$doc['_id'] = md5($c.$title);
		
		$doc['abstract_url'] = @$title_detail_url_map[$title];
		
		$doc = array_merge($doc, get_abstract_by_title($class, $classpath, $title));
		
		//$doc = array_merge($doc, get_index($class, $classpath, $title));
		$doc['index'] = get_index($class, $classpath, $title);
		$doc['index_url'] = isset($index_url_map[$title]) ? $index_url_map[$title] : "";
		$doc['ts'] = time();
		
		$docs[] = $doc;
	}
	fclose($fp);
	//exit;
	return $docs;
}

define("STANDARD_LEN", 21);
function save_2_mongo($docs)
{
	$len = count($docs)==0 ? 0 : count($docs[0]);
	if(count($docs)==0)
	{
		echo "Empty Array\n";
	}
	else if($len!=STANDARD_LEN)
	{
		echo "Element not enough\n";
	}
	else
	{
		$docLen = count($docs);
		try{
			$mongo = new Mongo("192.168.0.159"); //create a connection to MongoDB
			$db=$mongo->mydb; //选择mydb数据库
			$collection=$db->shuobo; //选择集合(选择’表’)
			
			$mongoDoc = array();
			for($i=0; $i<$docLen; $i++)//每50个插入一次，防止doc过大
			{
				$mongoDoc[] = $docs[$i];
				if(($i+1)%50==0)
				{
				  $ok = false;
				  try{
					$ok = $collection->batchInsert($mongoDoc);
				   }catch(Exception $e) 
				   {
					//var_dump($e);
					echo "batchInsert Error \n";
					 save("./non-utf-8.log", var_export($mongoDoc, true), "a+");
				   }
					if(!$ok)
					{
						echo "Mongodb Insert Error\n";
					}
					$mongoDoc = array();
					echo ".";
				}
				
			}
			if(count($mongoDoc)!=0)
			{
				$collection->batchInsert($mongoDoc);
			}
			
			$mongo->close();
			echo "save to mongodb ... [$docLen]Done\n";
		}
		catch(MongoConnectionException $e) 
		{
			#die($e->getMessage());
			echo $e;
		}
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
	echo "Usage \$php job.php N(N is A/B/C..J)";
	exit;
}
@mkDir("logs");
$classFolder = "./index/";
$class = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
$class = array("$key");
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