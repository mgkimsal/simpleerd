<?php

include("common.php");
$x = parse_ini_file("./dot.ini",true);
$image = $x['.meta']['project'] ?: "sample";
unset($x['.meta']);


if(file_exists("./$img.png")) { 
$image = "./$img.png";
}

$j = makeTablesAndRelations($x);
$tables = $j['tables'];
$relations = $j['relations'];



array_map('unlink', glob("./migrations/*php"));
@rmdir("./migrations");
@unlink("./migrations");
@mkdir("./migrations");

array_map('unlink', glob("./models/*php"));
@rmdir("./models");
@unlink("./models");
@mkdir("./models");


foreach($tables as $tableName=>$tableDef)
{
	$info = makeModel($tableName, $tableDef);
	$file = $info['file'];
	$name = $info['name'];
	$full = $name.".php";
	file_put_contents("./models/$full", $file);
	chmod("./models/$full", 0777);
}

foreach($tables as $tableName=>$tableDef)
{
	$info = makeTable($tableName, $tableDef);
	$file = $info['file'];
	$name = $info['name'];
	$full = date("Y_m_d_hms_").$name.".php";
	file_put_contents("./migrations/$full", $file);
	chmod("./migrations/$full", 0777);
}
sleep(1);

foreach($relations as $tableName=>$relDef)
{
	if(count($relDef)==0) { continue; }
	$info = makeRelation($tableName, $relDef);
	$file = $info['file'];
	$name = $info['name'];
	$full = date("Y_m_d_hms_").$name.".php";
	file_put_contents("./migrations/$full", $file);
	chmod("./migrations/$full", 0777);
}



makeReadme();
$zipname = 'data.zip';
@unlink($zipname);
`zip $zipname ./migrations/*`;
`zip $zipname ./models/*`;
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

Copy the 'models' files directly in to the app/ folder

EOD;
file_put_contents("./README.txt", $x);

}

function makeModel($tableName, $tableDef)
{

$tmp = str_replace(" ","", ucwords(str_replace("_", " ", $tableName)));
$className = $tmp;
$filename = $className;

$x = <<<EOD
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class $className extends Model
{

	protected \$table = "$tableName";

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
