<?php

function makeTablesAndRelations($data) {
	$x = $data;
	$tables = [];
	$relations = [];
	foreach($x as $k=>$v) {
	    if (!@$tables[$k]) {
		$tables[$k] = [];
		$relations[$k] = [];
	    }
	}
	foreach($x as $k=>$v) {
	$tables[$k][] = "id (int)";
	    foreach($v as $name=>$val)
	    {
		$field = explode(":", $val);
		$type = $field[0];
		$rel = null;
		$rel = @$field[1];
		$tables[$k][] = $name . "($type)";
		if($rel)
		{
//		    $relations[$k][(count($tables[$k])-1)] = $rel;
		    $relations[$k][$name] = $rel;
		}
	    }
//	$tables[$k][] = "date_created (date)";
//	$tables[$k][] = "date_updated (date)";
	}
	return ['tables'=>$tables, 'relations'=>$relations];
}



