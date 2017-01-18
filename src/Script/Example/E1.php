<?php

namespace Loquat\Script\Example;

class E1 extends \Loquat\Lib\Script{
    const _PRG_CNT_LIMITED_ = 1;//
    function run(){

        while($this->comeon()){
            echo date("Y-m-d H:i:s")."\n";
            sleep(1);
        }
    }
}

