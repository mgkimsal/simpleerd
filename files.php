<?php
$includeForeignKeys = @$_POST['foreignkeys'] ?: "no";

include("common.php");
$x = parse_ini_file("./dot.ini",true);
$image = $x['.meta']['project'] ?: "sample";
unset($x['.meta']);


if(file_exists("./$img.png")) {
$image = "./$img.png";
}

$j = makeTablesAndRelations($x);
$hasOne = $j['hasOne'];
$belongsTo = $j['belongsTo'];
$hasMany = $j['hasMany'];
$tables = $j['tables'];
$relations = $j['relations'];
//echo "<pre>"; print_r($hasOne);
//echo "<pre>"; print_r($belongsTo);die();
//echo "<pre>"; print_r($relations);die();
//echo "<pre>"; print_r($tables);die();


array_map('unlink', glob("./migrations/*php"));
@rmdir("./migrations");
@unlink("./migrations");
@mkdir("./migrations");

array_map('unlink', glob("./Models/*php"));
@rmdir("./Models");
@unlink("./Models");
@mkdir("./Models");


foreach($tables as $tableName=>$tableDef)
{
	$info = makeModel($tableName, $tableDef,
        @$belongsTo[$tableName] ?: [],
        @$hasOne[$tableName] ?: [],
        @$hasMany[$tableName] ?: []);
	$file = $info['file'];
	$name = $info['name'];
	$full = $name.".php";
	file_put_contents("./Models/$full", $file);
	chmod("./Models/$full", 0777);
}

foreach($tables as $tableName=>$tableDef)
{
	$info = makeTable($tableName, $tableDef['defs']);
	$file = $info['file'];
	$name = $info['name'];
	$full = date("Y_m_d_hms_").$name.".php";
	file_put_contents("./migrations/$full", $file);
	chmod("./migrations/$full", 0777);
}
sleep(1);

if($includeForeignKeys=="yes") {
    foreach ($relations as $tableName => $relDef) {
        if (count($relDef) == 0) {
            continue;
        }
        $info = makeRelation($tableName, $relDef);
        $file = $info['file'];
        $name = $info['name'];
        $full = date("Y_m_d_hms_") . $name . ".php";
        file_put_contents("./migrations/$full", $file);
        chmod("./migrations/$full", 0777);
    }
}



makeReadme();
$zipname = 'datamodels.zip';
@unlink($zipname);
`zip $zipname ./dot.ini`;
`zip $zipname ./migrations/*`;
`zip $zipname ./Models/*`;
`zip $zipname ./README.txt`;
`zip $zipname ./$image.png`;
    header('Content-Type: application/zip');
    header("Content-Disposition: attachment; filename='".$zipname."'");
    header('Content-Length: ' . filesize($zipname));
    header("Location: ".$zipname);




function makeReadme()
{
	$x = <<<EOD

Copy the 'migrations' files in to database/migrations folder

Copy the 'Models' files directly in to the app/ folder

EOD;
file_put_contents("./README.txt", $x);

}

function makeModel($tableName, $tableDef, $belongsTo, $hasOne, $hasMany)
{

$tmp = str_replace(" ","", ucwords(str_replace("_", " ", $tableName)));
$className = $tmp;
$filename = $className;

$uses = [];
foreach($hasOne as $c) { $m = ucfirst($c); $uses[$m] = $m; }
foreach($hasMany as $c) { $m = ucfirst($c); $uses[$m] = $m; }
$useList= "";
foreach($uses as $u) { $useList .= 'use App\Models\\' . $u . ";\n"; }

$x = <<<EOD
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
$useList
class $className extends Model
{

	protected \$table = "$tableName";

EOD;
    foreach($belongsTo as $k=>$v)
    {
        $upperCaseV = ucfirst($v);
        $x.= <<<EOD

    public function $v()
    {
        return \$this->belongsTo($upperCaseV::class);
    }
EOD;
    }
    foreach($hasOne as $k=>$v)
    {
        $upperCaseV = ucfirst($v);
        $x.= <<<EOD

    public function $v()
    {
        return \$this->hasOne($upperCaseV::class);
    }
EOD;
    }

    foreach($hasMany as $k=>$v)
    {
        $upperCaseV = ucfirst($v);
        $plural = $v."s";
        $x.= <<<EOD

    public function $plural()
    {
        return \$this->hasMany($upperCaseV::class);
    }
EOD;
    }


$x .= <<<EOD

}



EOD;

return ['name'=>$filename, 'file'=>$x];


}


function makeTable($tableName, $tableDef)
{

$tmp = str_replace(" ","", ucwords(str_replace("_", " ", $tableName)));
$className = "Create".$tmp;
$filename = "create_".strtolower($tableName);

$x = <<<EOD
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class $className extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('$tableName', function (Blueprint \$table) {
            \$table->increments('id')->unsigned();
EOD;

foreach($tableDef as $key)
{
	list($name,$type) = explode("(",$key);
	$type = str_replace(")","",$type);
	$name = trim($name);
	if($name=="id" || $name=="date_created" || $name=="date_updated") { continue; }
	if($type=="int") {
		$x.= "\n\t    \$table->unsignedInteger('$name');";
	}
	if($type=="decimal") {
		$x.= "\n\t    \$table->decimal('$name',14,8);";
	}
	if($type=="string") {
		$x.= "\n\t    \$table->string('$name');";
	}
	if($type=="bool" || $type=="boolean") {
		$x.= "\n\t    \$table->boolean('$name');";
	}
	if($type=="text") {
		$x.= "\n\t    \$table->text('$name');";
	}
	if($type=="date") {
		$x.= "\n\t    \$table->date('$name');";
	}
}

$x.= <<<EOD

            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('$tableName');
    }
}



EOD;

return ['name'=>$filename, 'file'=>$x];


}

function makeRelation($tableName, $relDef)
{

$tmp = str_replace(" ","", ucwords(str_replace("_", " ", $tableName)));
$className = "Add".$tmp;
$filename = "add_".strtolower($tableName);

$x = <<<EOD
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class $className extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('$tableName', function (Blueprint \$table) {
EOD;
//print_r($relDef);die();
	foreach($relDef as $name=>$foreign) {
            $x.= "\n\t    \$table->foreign('$name')->references('id')->on('$foreign');";
	}
$x .= <<<EOD

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}



EOD;

return ['name'=>$filename, 'file'=>$x];


}
