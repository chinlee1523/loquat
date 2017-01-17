<?php

namespace Loquat\Config;

class Process{//关于进程的配置
    const _PRG_LOCK_PRE_ = 'loquat_';//单机锁前缀

    static $dtdRedis = array(
        'prefix'  => 'loquat_',
        'servers' =>
        array(
            array(
                'ip'        => '127.0.0.1',
                'port'      => 6379,
                'timeout'   => 300,
                'keepalive' => true,
            ),
        ),

    );

    const _DTD_LOCK_PRE_ = 'loquat_dtd_';//分布式锁前缀
}

