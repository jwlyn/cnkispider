<?php 
require_once "./lib/function.php";

function resourceReplace($content)
{
	$path = "../../css/";
	$script = '<script type="text/javascript" src="'. $path .'WebResource1.js" ></script>';
	$script .= '<script type="text/javascript" src="'.$path.'WebResource2.js" ></script>';
	$script .= '<meta http-equiv="content-type" content="text/html; charset=UTF-8" />';
	
	$patterns = array(
		'/\/kreader\/css\/GB_min\/tree.min.css/'=>$path . 'tree.min.css',
		'/\/kreader\/css\/GB_min\/global.min.css/'=>$path . 'global.min.css',
		'/Scripts\/jquery-1.6.1.min.js/'=>$path . 'jquery-1.6.1.min.js',
		'/\/kreader\/scripts\/min\/treeNew.min.js/'=>$path . 'treeNew.min.js',
		
		'/Images\/TreeLineImages\/r.gif/'=>$path . 'r.gif',
		'/Images\/TreeLineImages\/t.gif/'=>$path . 't.gif',
		'/Images\/TreeLineImages\/i.gif/'=>$path . 'i.gif',
		'/Images\/TreeLineImages\/l.gif/'=>$path . 'l.gif',
		'/Images\/TreeLineImages\/tminus.gif/'=>$path .'tminus.gif',
		'/Images\/TreeLineImages\/lminus.gif/'=>$path .'lminus.gif',
		
		'/\/kreader\/WebResource.axd/' => $path. 'WebResource.axd',
		
		'/<\/head>/'=>$script . '</head>',
		'/<input type="hidden".*?\/>/'=>'',
		'/href="javascript.*?">/'=>'href="#">',
		'/onclick ="(.*?)"/'=>' ',
	);
	foreach($patterns as $pattern=>$replace)
	{
		$content = preg_replace($pattern, $replace, $content);
	}
	
	return $content;
}

function save1($file, $content, $mod="w+")
{
	if(!$content)
	return;
	$file = iconv("utf-8","gb2312", $file);
	$fp = fopen($file, $mod);
	fwrite($fp, $content);
	fclose($fp);
}



?>

<?php

$key = $argv[1];
if(!$key)
{
	echo "usage \$php getindex.php 'A', 'B' ...\n";
	exit;
}

$files = get_all_log_file("./data/$key/");
makeDir("./data/index/");//存放论文目录,不会重复创建
makeDir("./data/index/$key");
foreach($files as $file)//每个文件的
{
	$fp = fopen($file, "r");
	$file = iconv("gb2312","utf-8", $file);
	$subdir = basename($file, ".log");
	$subdir = win_dir_format($subdir);
	$indexSavePath = "./data/index/$key/" . $subdir;
	makeDir($indexSavePath);
	
	$mapFile = $indexSavePath . "/paper_url_mapping.log";
	$icount = 1;
	while($line=readLine($fp))//每一行
	{
		$arr  = explode("\t", $line);
		$u = $arr[6];
		$paperName = $arr[0];
		$paperName = win_dir_format($paperName);
		
		$dbCode = get_db_code($u);
		$fileName = get_file_name($u);
		$tableName = get_table_name($u);

		$realUrl = get_real_url($dbCode, $fileName, $tableName);
		$indexContent = file_get_contents($realUrl);
		$htmlFileName = $indexSavePath . "/" . $paperName . ".html";
		$tmpFile = iconv("utf-8","gb2312", $htmlFileName);

		if(file_exists($tmpFile))
		{
			echo "Cache hit! continue -> $htmlFileName\n";
			continue;
		}
		$indexContent = resourceReplace($indexContent);

		save1($htmlFileName, $indexContent);
		
		$mapContent = "$paperName\t$realUrl\n";
		save1($mapFile, $mapContent, "a+");
		echo "[" . $icount++ . "] " ."Save file $htmlFileName\n";
		fakeSleep();
	}
}







?>