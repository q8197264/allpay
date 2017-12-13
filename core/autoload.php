<?php
/**
 * 自动加载
 * User: 刘孝全
 * Date: 2016/6/29
 * Time: 17:00
 */
//自动加载类
$root_dir = array(
    dirname(__DIR__).'/db',
    dirname(__DIR__).'/abstract',
    dirname(__DIR__).'/gateway',
    dirname(__DIR__).'/lib',
);

//自动加载函数
spl_autoload_register(function ( $classname ) use($root_dir)
{
    if (!class_exists($classname))
    {
        foreach($root_dir as $dir){
            $file = $dir.'/'.$classname.'.php';
            if (is_file($file)) {
                require_once($file);
                return true;
            }
        }
    }else{
        CY_log::add("redeclare class {$classname}");
    }
},true);