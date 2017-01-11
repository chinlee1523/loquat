<?php
/**
 * Created by JetBrains PhpStorm.
 * User: carrotli@qq.com
 * Date: 16/12/17
 * Time: 21:36
 */

namespace Loquat\Lib;

use Loquat\Config as Config;
use Loquat\Util as Util;

abstract class Script{

    /**类的相关配置**/
    const _PRG_CNT_LIMITED_ = 1;//单机进程限制数量
    const _DTD_LOCK_        = true;//是否需要分布式锁
    const _MUTEX_OR_SPIN_   = true;//true为互斥锁 false为自旋锁
    const _LIFE_CYCLE_      = 60;//进程生命周期 运行时间超过生命周期就退出 以便其他机器也有机会运行此服务
    static $RUNTIMES = array(    //进程运行周期
        array(
            'minutes' =>
            array(
                array("00", "60"),
            ),
            'hours' => array(),
            'weeks' => array(),
            'days' =>
            array(
                array('01','31'),
            ),
            'months' => array(),

        ),
    );
    protected $runTimes       = null;//进程运行时间段

    protected $prgCntLimited  = 0;//单机进程个数限制
    protected $prgLock        = null;//单机进程锁 其实是文件句柄

    protected $dtdLockHandle  = null;//分布式锁 Redis实现
    protected $dtdLockRes     = null;//分布式锁结果
    protected $lifeCycle      = 0;//服务生命周期
    protected $mutexOrspin    = true;//锁类型   true为互斥锁 false为自旋锁
    protected $startTime      = 0;//开始运行时间戳
    protected $personality    = null;//服务特征

    protected $argv           = null;//进程参数

    public function __construct(){

        $this->initSingalHandler();//初始化处理信号的函数
        $this->initRuntimes();
        if(!$this->inRuntimes()){ //不在运行时间段内
            //add log!
            exit();
        }

        $this->initPrgCntLimited();//初始化单机进程数量限制
        $this->initArgv();//初始化参数
        $this->initPersonality();//初始化服务特征
        $this->initPrgLock();//初始化单机锁
        if($this->prgCntLimited != -1){//单机进程数不受限制
            if(empty($this->prgLock)){//获得单机锁失败 退出
                //add log!
                exit();
            }
        }

        $this->doDtdLock();//处理分布式锁

        $this->initStartTime();//获得服务开始运行时间

    }

    public function __destruct(){

        $this->doExiting();
    }
    //初始化服务开始运行时间
    protected function initStartTime(){
        if(empty($this->startTime)){
            $this->startTime = time();
        }

    }

    //初始化处理信号的函数
    protected function initSingalHandler(){
        pcntl_signal(SIGHUP, array(&$this, 'signal_handler'));
        pcntl_signal(SIGINT, array(&$this, 'signal_handler'));

    }
    //处理信号的函数
    private function signal_handler(int $signo){
        //add log
        $this->doExiting();
    }

    //进程退出时的处理
    protected function doExiting(){

        $this->deDtdLock();//释放分布式锁
        $this->deDtdLockHandle();//释放分布式锁句柄
        $this->dePrgLock();//释放单机进程锁

    }
    //初始化锁类型
    protected function initMutexOrSpin(){
        $class = get_called_class();
        $this->mutexOrspin = $class::_MUTEX_OR_SPIN_;
    }
    //处理分布式锁
    protected function doDtdLock(){
        $this->initDtdLockHandle();//初始化分布式锁句柄
        $this->initMutexOrSpin();
        while($this->prgCntLimited == 1){//单进程处理 需要来处获得锁
            $this->initDtdLock();
            if(empty($this->dtdLockRes)){//获得分布式锁失败
                if($this->mutexOrspin){//互斥锁 直接退出
                    exit();
                }
                else{//自旋锁 继续等待
                    sleep(1);
                    contine;
                }

            }
            else{//获得锁成功
                break;
            }
        }

    }
    protected function comeon(){

        if(!$this->inRuntimes()){//不在运行时间段
            return false;
        }
        if($this->LifeIsOver()){//生命周期已结束
           return false;
        }

        return true;
    }
    //是否已过生命周期
    protected function LifeIsOver(){
        $this->initLifeCycle();
        return (time()-$this->startTime >= $this->lifeCycle);
    }
    //初始化进程生命周期
    protected function initLifeCycle()
    {
        $class = get_called_class();
        $this->lifeCycle = $class::_LIFE_CYCLE_;
    }
    //获得分布式锁
    protected function initDtdLock(){
        if($this->prgCntLimited == 1 && is_object($this->dtdLockHandle)){
            $resource = Config\Process::_DTD_LOCK_PRE_ . $this->personality;
            $this->initLifeCycle();

            $this->dtdLockRes = $this->dtdLockHandle->lock($resource, $this->lifeCycle * 1000);
        }

    }
    //释放分布式锁句柄
    protected function deDtdLockHandle(){
        if(!empty($this->dtdLockHandle) && is_object($this->dtdLockHandle))
        {
            unset($this->dtdLockHandle);
            $this->dtdLockHandle = null;
        }
    }
    //初始化分布式锁句柄
    protected function initDtdLockHandle(){
        $class = get_called_class();
        if($class::_DTD_LOCK_ && empty($this->dtdLockHandle)){
            $this->dtdLockHandle = new Util\DistributedLock(Config\Process::$dtdRedis);
        }
    }
    //初始化进程参数
    protected function initArgv(){
        global $argv;
        $this->argv = $argv;
    }
    //释放分布式锁
    protected function deDtdLock(){
        if(!empty($this->dtdLockRes)){
            $this->dtdLockHandle->unlock($this->dtdLockRes);
            $this->dtdLockRes = null;
        }
    }
    //释放单机锁
    protected function dePrgLock(){

        if(!empty($this->prgLock)){
            flock($this->prgLock, LOCK_UN);
            fclose($this->prgLock);
            $this->prgLock = null;
        }

    }
    //获得单机锁
    protected function initPrgLock(){
        $this->initPrgCntLimited();

        if($this->prgCntLimited != -1){//-1时为单机进程没有数量限制

            $lockPre = Config\Process::_PRG_LOCK_PRE_;
            for($i=0;$i<$this->prgCntLimited;$i++){

                $lockFileName =  "/tmp/.{$lockPre}{$this->personality}_{$i}";
                $lockFp = null;

                if($lockFp = fopen($lockFileName,'w')){

                    if(flock($lockFp, LOCK_EX|LOCK_NB)){
                        $this->prgLock = $lockFp;
                        break;
                    }
                    else{
                        fclose($lockFp);
                    }
                }
            }
        }
    }
    //初始化单机进程个数限制
    protected function initPrgCntLimited(){
        if(empty($this->prgCntLimited)){
            $class = get_called_class();
            $this->prgCntLimited = $class::_PRG_CNT_LIMITED_;
        }
    }
    //初始化运行时间段
    protected function initRuntimes(){
        if(empty($this->runTimes)){
            $class = get_called_class();
            $this->runTimes = $class::$RUNTIMES;
        }
    }
    //是否在运行时间段
    protected function inRuntimes(){
        $minute = null;
        $hour = null;
        $month = null;
        $week = null;
        $day = null;
        if(empty($this->runTimes)){
            return true;
        }
        foreach($this->runTimes as $runtime){
            $true = true;
            if(!empty($runtime['minutes'])){
                $minute = $minute==null?date("i"):$minute;
                foreach($runtime['minutes'] as $m){
                    if($minute < $m[0] || $minute > $m[1]){
                        $true = false;
                    }
                }

            }
            if($true && !empty($runtime['weeks'])){
                $week = $week==null?date("w"):$week;
                if(!in_array($week, $runtime['weeks'])){
                    $true = false;
                }
            }
            if($true && !empty($runtime['months'])){
                $month = $month==null?date("m"):$month;
                if(!in_array($month, $runtime['months'])){
                    $true = false;
                }
            }
            if($true && !empty($runtime['hours'])){
                $hour = $hour==null?date("H"):$hour;
                if(!in_array($hour, $runtime['hours'])){
                    $true = false;
                }
            }
            if($true && !empty($runtime['days'])){
                $day = $day==null?date("d"):$day;
                foreach ($runtime['days'] as $d){
                    if($day < $d[0] || $day > $d[1]){
                        $true = false;
                    }
                }

            }
            if($true){
                return $true;
            }
        }
        return false;
    }
    //初始化服务特征值
    protected function initPersonality(){
        $argv = $this->argv;
        array_shift($argv);
        $this->personality = str_replace("\\", "_", implode('_', $argv));
    }

}