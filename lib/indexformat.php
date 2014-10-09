<?php 
/**
 * 输入一个目录的原始网页，
 * 输出目录的简略版本。
 * 
 */
include_once "simple_html_dom.php";
include_once "function.php";

function judge_lv(&$table)
{
	return count($table->find("table"));
}

function simple_index($file_path)
{
	$result = "";
	$pth = $file_path;//iconv("utf-8", "gb2312", $file_path);

	if(file_exists($pth))
	{
		$html = file_get_html($pth);
		if($html)
		{
			$tables_lv1 = $html->find("table[cellpadding=0]");
			foreach($tables_lv1 as &$table1)
			{
				$text = $table1->plaintext;
				$text = trim($text);
				$lv = judge_lv($table1);
				$text = '<p class="p_index p_' . $lv . '">' . $text . '</p>' . "\n";
				$result .= $text;
				//unset($table1);
			}
			//unset($tables_lv1);
			$html->clear();
		}
		
		unset($html);
	}
	else
	{
		die("$file_path not exits") ;
	}

	return $result;
}

//$c = simple_index("./1.html");
//save("./test.html", $c);






