<?php
include("common.php");

/**
 * Created by michael.
 * Date: 6/13/17 Time: 7:45 PM
 */

$y = $_POST['data'];
$time = time();
file_put_contents("./dot.ini",$y);
//$x = parse_ini_string($y,true);
$x = parse_ini_file("./dot.ini",true);
$image = $x['.meta']['project'] ?: "sample";
unset($x['.meta']);
$j = makeTablesAndRelations($x);
$tables = $j['tables'];
$relations = $j['relations'];


$d = makeDot($tables, $relations);
file_put_contents("./out.dot", $d);
`dot -Tpng -O ./out.dot`;
`cp ./out.dot.png ./$image.png`;
header("Location: index.php?time=$time");
die();

function makeDot($tables, $relations)
{
    $dot = <<<EOD
digraph g {
graph [
    rankdir = "LR"
]
 node [
     fontsize = "12"
     shape = "plaintext"
 ]
EOD;

    foreach($tables as $table=>$fields)
    {
        $dot .= "$table [\n";
        $dot .= <<<EOD
label = <<table bgcolor="#FAFAFA" border="0" cellborder="1" cellspacing="0" cellpadding="4">
        <tr><td align="center" bgcolor="#CCEEEE">
         $table
         </td></tr>
EOD;

        foreach($fields as $port=>$name)
        {
            $p = "f".$port;
                $b = "FFFFFF";
            $dot .= <<<EOD
            
<tr><td align="center" bgcolor="#$b" port="$p">
 $name
 </td></tr>

EOD;

        }
        $dot .= <<<EOD
        </table>
>
EOD;

        $dot .= "\n]\n";

        if(@$relations[$table])
        {
            foreach($relations[$table] as $port=>$rel)
            {
                $dot .= <<<EOD
 "$table":f$port -> "$rel" []
 
EOD;

            }
        }

    }


    $dot .= <<<EOD
}
EOD;

    return $dot;

}

/**
 *  example dot
 * digraph g {
 graph [
     rankdir = "LR"
 ]
 node [
     fontsize = "12"
     shape = "plaintext"
 ]

 employee [
     label = <<table bgcolor="#FAFAFA" border="0" cellborder="1" cellspacing="0" cellpadding="4">
 <tr><td align="center" bgcolor="#CCCCEE" port="f0">
 employee
 </td></tr>
 <tr><td align="left" balign="left" port="f1">
 name
 </td></tr>
 <tr><td align="left" balign="left" port="f2">
 age
 </td></tr>
 <tr><td align="left" balign="left" port="f3">
 department
 </td></tr>
 <tr><td align="left" balign="left" port="f4">
 manager
 </td></tr>
 </table>>
 ]
 "employee":f3 -> "department" []
 "employee":f4 -> "employee" []
 department [
     label = <<table bgcolor="#FAFAFA" border="0" cellborder="1" cellspacing="0" cellpadding="4">
 <tr><td align="center" bgcolor="#CCCCEE" port="f0">
 department
 </td></tr>
 <tr><td align="left" balign="left" port="f1">
 name
 </td></tr>
 </table>>
 ]

 }

 */
