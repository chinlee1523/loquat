<?php
/**
 * Created by JetBrains PhpStorm.
 * User: carrotli@qq.com
 * Date: 16/12/17
 * Time: 21:28
 * To change this template use File | Settings | File Templates.
 */

ini_set('date.timezone','Asia/Shanghai');

global $appNameSpace;
require_once('../autoload/autoload.php');
$appNameSpace = 'Loquat';

global $argv;

try{

    if(count($argv)<2){

        die();
    }
    $className = $argv[1];
    if(empty($className)){
        //add log
        die();
    }

    $className = $appNameSpace . "\\Script\\{$className}";

    if(!class_exists($className)){
        //add log
        die();
    }
    $script = new $className;
    $script->run();
}
catch (\Exception $ex){
   //add log
}
