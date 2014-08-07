<?php 
/**
 * 生成parent索引和children索引
 * parent索引可以根据当前分类找到自己的母分类
 * children索引可以根据母分类找到全部都额子分类
 *
 */
require_once "./lib/function.php";
 
function get_file_path($code)
{
	return "./index/$code/_result_$code.log";
}
?>


<?php 
$temp_file = "./c_p";
$c_p_array = array();
$p_c_array = array();//单key多值数组

$class = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
//$class = array("A");
foreach($class as $c)
{
	$path = get_file_path($c);
	$fp = fopen($path, "r");
	while($line=readLine($fp))
	{
		$arr = explode(" ", $line);
		if(count($arr)!=2) continue;
		$line = $arr[0];
		$arr = explode("#", $line);
		$len = count($arr);
		for($i=1; $i<$len; $i++)
		{
			$c_p_array[$arr[$i]] = $arr[$i-1];
		}
		$p_c_array[$arr[$len-1]] = array();//最后一个是叶子节点，下面就是具体文章了
	}
	
	fclose($fp);
}
save("./c_p.php", var_export($c_p_array, true));

/* 创建p_c */

foreach($c_p_array as $key=>$value)
{
	if(!isset($p_c_array[$value]))
	{
		$p_c_array[$value] = array();
	}
	
	$children = &$p_c_array[$value];
	$children[] = $key;
}

save("./p_c.php", var_export($p_c_array, true));

?>