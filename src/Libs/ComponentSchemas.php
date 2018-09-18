<?php
/**
 * APi核心的相关
 * User: blues
 * Date: 2017/9/20/020
 * Time: 18:03
 */
namespace Larfree\Libs;


class ComponentSchemas extends Schemas
{


    /**
     * 获取蓝图的组建字段
     * @param $name
     * @param $target
     * @return array
     */
    static function getSchemasConfig($name,$target)
    {
        $target = strtolower($target);


        $file = dirname(dirname(dirname(__FILE__))) . '/config/Schemas/Components/' . self::fomartName($name) . '.php';

        $GlobalSchemas = self::getSchemas($name);//主结构
        if (file_exists($file)) {
            $Schemas = include $file;
            $Schemas = @$Schemas['detail'][$target];
            $field = self::formatFields(@$Schemas['fields']);
            $search = self::formatFields(@$Schemas['search']);
            $filter_field = [];
            //合并结构
            if($field) {
                foreach ($field as $key => $f) {
                    //如果有group_children字段,那是分组用的
                    if (!isset($f['group_children'])) {
                        $filter_field[$key] = '';//用来筛选字段
                        if ($f) {
                            $GlobalSchemas[$key] = $f + $GlobalSchemas[$key];
                        }
                    } else {
                        //是分组的,循环一次,合并结构
                        foreach ($f['group_children'] as $group_key => $group_field) {
                            if (is_array($group_field)) {
                                $filter_field[$group_key] = '';
                                $GlobalSchemas[$group_key] = $group_field + $GlobalSchemas[$group_key];
                            } else {
                                $filter_field[$group_field] = '';
                            }
                        }
                    }//endif
                }
                $filter_field = array_intersect_key($GlobalSchemas, $filter_field);


                //如果有分组的,对分组数据进行重构,以及字段排序
                array_walk($field, function (&$val, $key) use ($filter_field) {
                    if (isset($val['group_children'])) {
                        $group_children=[];
                        foreach($val['group_children'] as $k=>$v){
                            if(!is_array($v))
                                $group_children[$v] = $filter_field[$v];
                            else
                                $group_children[$k] = $filter_field[$k];
                        }
                        $val['group_children'] = $group_children;
                    } else {
                        $val = $filter_field[$key];
                    };
                });
            }
            //带有分组和其他结构的
            $Schemas['component_fields'] = $field;
            //转成1维数组的
            $Schemas['fields'] = $filter_field;
            //搜索的结构
            if(@$search) {
                foreach($search as $key =>$f){
                    if($f){
                        $GlobalSchemas[$key]=$f+$GlobalSchemas[$key];
                    }
                }
                $search = array_intersect_key($GlobalSchemas, $search);
                $Schemas['search'] = $search;
            }

        }else{
            $field = self::getSchemas($name);
            $Schemas  = ['fields'=>$field,'component_fields'=>$field];//主结构
        }
        return $Schemas;
    }

    /**
     * 获取组建的默认配置
     * @param $path  ui.tab
     * @return mixed
     */
    static public function getComponetDefConfig($path,$config,$target=''){
        $name = str_replace('.','/',$path);
        $cpath = dirname(dirname(dirname(__FILE__))).'/config/Schemas/Components/Default/'.self::fomartName($name).'.php';
        if(file_exists($cpath)) {
             $func = include dirname(dirname(dirname(__FILE__))) . '/config/Schemas/Components/Default/' . self::fomartName($name) . '.php';
            return $func($config,$path,$target);
        }else
            return [];
    }

    /**
     * 获取组建的最终参数
     * @param $url test.test|chart.line.line
     */
    static public function  getComponentConfig($schemas,$action){

        $target = explode('.',$action);
        //根据chart.line.chart  chart.line  chart 3种不同模式,进行解析
        switch (count($target)){
            case 1:
                $config = ComponentSchemas::getSchemasConfig($schemas,$target[0]);
                break;
            case 2:
                $config = ComponentSchemas::getSchemasConfig($schemas,$target[1]);
                $config = ComponentSchemas::getComponetDefConfig($action,$config,$target[1]);
                break;
            case 3:
                $config = ComponentSchemas::getSchemasConfig($schemas,$target[2]);
                $action = implode('.',array_slice($target,0,2));
                $config = ComponentSchemas::getComponetDefConfig($action,$config,$target[2]);
                break;
        }
        return $config;
    }


}