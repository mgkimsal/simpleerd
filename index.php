<?php
$x = file_get_contents("./dot.ini");
$j = parse_ini_string($x,true);
if(!$time=@$j['.meta']['time'])
{
	$time = @$_GET['time'] ?: time();
}
if(strpos($x,".meta")>0) {
//	list($top, $junk) = explode("[.meta]",$x);
//	$x = $top;
	$img = @$j['.meta']['project'] ?: "sample";
} else {
	$x .= "\n[.meta]\nproject=sample";
	$img = "sample";
}
$image = "";
if(file_exists("./$img.png")) { 
$image = "./$img.png";
}
?>
<html>
<form method="post" action="post.php">

<textarea name="data" rows="20" cols="80">
<?=$x;?>
</textarea>
<br/>
<input type="submit" value="Post"/>
</form>
<form method='post' action='files.php'>
<input type="submit" value="Get files"/>
</form>
<?php if($image!="") { ?>
<img src="<?=$image;?>?time=<?=$time;?>"/>
<?php } else { ?>
No image exists yet
<?php } ?>
</html>

