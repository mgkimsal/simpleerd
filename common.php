<?php

function makeTablesAndRelations($data)
{
    $hasOne = [];
    $belongsTo = [];
    $hasMany = [];

    $x = $data;
    $tables = [];
    $relations = [];
    foreach ($x as $k => $v) {
        if (!@$tables[$k]) {
            $tables[$k] = [];
            $relations[$k] = [];
        }
    }
    foreach ($x as $k => $v) {
        $tables[$k]['defs'][] = "id (int)";
        foreach ($v as $name => $val) {
            $field = explode(":", $val);
            $type = $field[0];
            $rel = null;
            $rel = @$field[1];
            $tables[$k]['defs'][] = $name . "($type)";
            if ($rel) {
                $relations[$k][$name] = $rel;
                list($first,$id) = explode("_", $name);
                if($id=="id") {
                    $hasOne[$k][] = $first;
                    // if this is a join table (user_skill, etc)
                    // then we'll make is a hasmany otherwise it's a belongsto
                    if(strpos($k,"_")===false)
                    {
                        $belongsTo[$first][] = $k;
                    } else {
                        $joins = explode("_", $k);
                        unset($joins[array_search($first, $joins)]);
                        $hasMany[$first][] = $joins[array_key_first($joins)];
                    }
                }

            }
        }
//	$tables[$k][] = "date_created (date)";
//	$tables[$k][] = "date_updated (date)";
    }
    return ['hasMany'=>$hasMany, 'hasOne'=>$hasOne, 'belongsTo'=>$belongsTo, 'tables' => $tables, 'relations' => $relations];
}



