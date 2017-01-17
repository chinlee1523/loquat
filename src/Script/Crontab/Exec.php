<?php

namespace Loquat\Script\Crontab;

class Exec extends \Loquat\Lib\Script{//获得执行脚本的命令
    const _PRG_CNT_LIMITED_ = -1;//
    function run(){
        $funcName = "get".$this->argv[2];
        $initFuncName = "init".$this->argv[2];
        if(!method_exists("\\Loquat\\Config\\Crontab",$funcName)){
            //add log
            return;
        }
        if(!method_exists("\\Loquat\\Config\\Crontab",$initFuncName)){
            //add log
            return;
        }
        Config\Crontab::$initFuncName();
        $Execs = Config\Crontab::$funcName();
        foreach($Execs as $p){
            echo implode(" ", $p)."\n";
        }

    }
}

