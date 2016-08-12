<?php
/*
svn://115.29.44.102/web/ecstore/trunk/ecstore_online/app/base/lib/db/model.php
svn://115.29.44.102/web/ecstore/trunk/ecstore_online/app/apiactionlog/lib/finder/builder/panel/render.php
*/
// 2016.08.12
error_reporting( E_ALL ^ E_NOTICE );
header( 'content-type:text/html;charset=utf-8;' );
$copyrightHTML = "/**\n * ChinaBUG @ SDEV\n *\n * @copyright  Copyright (c) 1999-%s WWW.IPODMP.COM. ( HTTP://BLOG.IPODMP.COM )\n * \n * %s @ %s\n**/";
$mustAppTran = array( 'label', 'comment' );
//
if( isset($_GET[ 'file' ]) && file_exists( $_GET[ 'file' ] ) ){
    include( $_GET[ 'file' ] );
    $_POST['db'][key($db)] = current($db);
    foreach( $_POST['db'] as $dbkey => $dbval ){
        foreach( $dbval[ 'index' ] as $dbIndexKey => $dbIndexVal ){
            unset( $_POST['db'][ $dbkey ][ 'index' ][ $dbIndexKey ] );
            $_POST['db'][ $dbkey ][ 'index' ][ $dbIndexKey ] = $dbIndexVal[ 'columns' ][ 0 ];
        }
    }
    // print_r( $_POST['db'] );
    // exit;
}

// 
if( $_POST['db'] ){
    // common...
    $year = date( 'Y' );
    $date = date( 'Y.m' );
    //
    $s_true = 'true';
    $s_false = 'false';
    foreach( $_POST['db'] as $keyIndex => $valIndex ){
        
        // print_r($valIndex);
        // exit;
        
        if( !isset($valIndex[ 'tablename' ]) || empty($valIndex[ 'tablename' ])  ){
            if( !$keyIndex ){
                continue;
            }else $valIndex[ 'tablename' ] = $keyIndex;
        }
        $valIndex[ 'tablename' ] = strtolower( $valIndex[ 'tablename' ] );
        $outputcode = array();
        $outputcode[] = "<?php";
        $outputcode[] = '';
        $tableCommment = $valIndex[ 'comment' ] . '( ' . $valIndex[ 'tablename' ] . ' )';
        $outputcode[] = sprintf( ($_POST[ 'commentTmp' ]?$_POST[ 'commentTmp' ]:$copyrightHTML),$year,$tableCommment,$date );
        $outputcode[] = '';
        $outputcode[] = "\$db['{$valIndex[ 'tablename' ]}']=array (";
        $outputcode[] = "    'engine'    => '{$valIndex[ 'engine' ]}',";
        $outputcode[] = "    'version'   => '{$valIndex[ 'version' ]}',";
        $outputcode[] = "    'comment'   => app::get('b2c')->_('{$valIndex[ 'comment' ]}'),";
        //
        $outputcode[] = "    'columns'   => array (";
        if( $valIndex[ 'columns' ] ){
            foreach( $valIndex[ 'columns' ] as $keyCol => $valCol ){
                $index_list[] = $keyCol;
                $outputcode[] = "        '{$keyCol}'   => array (";
                if( $valCol ){
                    // 防止出现字段没有数据类型的情况
                    if( !isset($valCol['type']) || empty($valCol['type']) || ($valCol['type']=='-') ) $valCol['type'] = 'tinyint(1)';
                                        
                    foreach( $valCol as $keyColItem => $valColItem ){
                        //支持直接导入格式化，所以存在直接的数组形式，再下面的逻辑中需要做一下处理
                        if( is_array($valColItem) ){
                            // echo '1.0 ' . $keyCol . ' - ' . $keyColItem . '  -  ' . $valColItem . "<br/>\n";
                            $outputcode[] = "                '{$keyColItem}'   => array(";
                            foreach( $valColItem as $iTkey => $iTval ){
                                if( !is_numeric($iTkey) ) $iTkey = "'{$iTkey}'";
                                if( gettype($iTval) != 'string'){
                                    $outputcode[] = "                    {$iTkey}   => {$iTval},";
                                }else{
                                    $outputcode[] = "                    {$iTkey}   => app::get('b2c')->_('{$iTval}'),";
                                }
                            }
                            $outputcode[] = "                ),";
                        }else{
                            // echo '2.0 ' . $keyCol . ' - '  . $keyColItem . '  -  ' . $valColItem . '   -   '  . gettype($valColItem) . "<br/>\n";
                            if( ($valColItem === true) || ($valColItem === 0) || ($valColItem === '0') || (gettype($valColItem)=='boolean') ){
                                // echo '2.1 ' . $keyCol . ' - '  . $keyColItem . '  -  ' . $valColItem . "<br/>\n";
                                if($keyColItem != 'default'){
                                    $outputcode[] = "                '{$keyColItem}'   => ".($valColItem ? $s_true : $s_false).",";    
                                }else $outputcode[] = "                '{$keyColItem}'   => '{$valColItem}',";
                            }else{
                                // echo '2.2 ' . $keyCol . ' - '  . $keyColItem . '  -  ' . $valColItem . "<br/>\n";
                                if( ($valColItem != '-') && !empty($valColItem) ){
                                    // 针对类型是枚举类型的做处理
                                    if( strstr($valColItem,'custom_enum') ){
                                        $outputcode[] = "                '{$keyColItem}'   => array(";
                                        $itemTmp = str_ireplace( array('custom_enum(\'','\')'), '', $valColItem );
                                        $itemTmp = explode( '\' , \'', $itemTmp );
                                        foreach( $itemTmp as $iTkey => $iTval ){
                                            $iTvalTmp = explode( '|', $iTval );
                                            if( !is_numeric($iTvalTmp[0]) ) $iTvalTmp[0] = "'{$iTvalTmp[0]}'";
                                            $outputcode[] = "                    {$iTvalTmp[0]}   => app::get('b2c')->_('{$iTvalTmp[1]}'),";
                                        }
                                        $outputcode[] = "                ),";
                                    }else{
                                        if( ($valColItem==='true') || ($valColItem==='false') ){
                                            $outputcode[] = "                '{$keyColItem}'   => {$valColItem},";
                                        }else{
                                            if( in_array($keyColItem,$mustAppTran) ){
                                                $outputcode[] = "                '{$keyColItem}'   => app::get('b2c')->_('{$valColItem}'),";
                                            }else{
                                                $outputcode[] = "                '{$keyColItem}'   => '{$valColItem}',";
                                            }
                                        }
                                    }
                                }
                            }
                        }                        
                    }
                }
                $outputcode[] = "        ),";
            }           
        }
        $outputcode[] = "    ),";
        //
        $outputcode[] = "    'index'     => array (";
        if( $valIndex[ 'index' ] ){
            foreach( $valIndex[ 'index' ] as $keyCol => $valCol ){
                if( !in_array( $valCol, $index_list ) ) continue;
                $outputcode[] = "        '{$keyCol}'   => array (";
                $outputcode[] = "            'columns'   => array (";
                $outputcode[] = "                0   => '{$valCol}',";
                $outputcode[] = "            ),";
                $outputcode[] = "        ),";
            }           
        }
        $outputcode[] = "    ),";
        $outputcode[] = ");";
        
        // print_r( $outputcode );
        // exit;
        
        
        // 转存
        ob_start();
            echo implode( "\n", $outputcode );
            $contents = ob_get_contents();
        ob_end_clean();
        file_put_contents( 'dbschema/' . strtolower( $valIndex[ 'tablename' ] ) . '.php', $contents );        
    }
    //
    exit( '<a href="javascript:history.go(-1);">生成完成!!!</a>' );
}

// 初始载入已有的结构
$dir = 'dbschema';
if( is_dir( $dir ) ){
    $folder = opendir( $dir );
    while( ( $fp = readdir( $folder ) ) != null ){
        if( ($fp != '.') && ($fp != '..') && ( $pathinfo = pathinfo( $dir . '/' . $fp ) ) && ($pathinfo['extension']=='php') ){
            include( $dir . '/' . $fp );
        }
    }
    if( !$db ) defaultDB( $db );
}


// 兼容原系统的调用
class app{
    static private $__instance = array();
    function __construct($app_id){}
    static function get($app_id){
        if(!isset(self::$__instance[$app_id])){
            self::$__instance[$app_id] = new app($app_id);
        }
        return self::$__instance[$app_id];
    }
    public function _($key)
    {
        return $key;
    }
}



// 生成界面元素
function gentHTML( $array = null ){
	
    parse_str(http_build_query(typeToCN()),$typeToCName );
    $typeDefault = typeDefault();
    //
    $OpOptionItem[0] = '<span class="btn-edit" title="编辑字段属性">✎</span>';
    $OpOptionItem[1] = '<span class="btn-del" title="清空字段属性">✖</span>';
    $OpOptionItem[2] = '<span class="btn-ts" title="翻译">🇹🇸</span>';
    $OpOption = $OpOptionItem[0].$OpOptionItem[1].$OpOptionItem[2];
    // 
    if( !$array ) defaultDB( $array );
    //
    echo '<form method="POST" action="">';
	
	// 文件注释内容
	echo "<textarea name=\"commentTmp\">{$copyrightHTML}</textarea>";
	
    foreach( $array as $key => $val ){
        $dbtableName = $key;
        $dbtablePro  = $val;
        
        $outHtml = array();
        // 第一层单表的通用属性
        $outHtml[] = '<fieldset id="'.$dbtableName.'" class="fs-form">';
        //
        $outHtml[] = "<input type=\"hidden\" name=\"db[{$dbtableName}][engine]\" value=\"{$val['engine']}\"/>";
        $outHtml[] = "<input type=\"hidden\" name=\"db[{$dbtableName}][version]\" value=\"{$val['version']}\"/>";
        $outHtml[] = "<legend>表（<input class=\"tblName\" type=\"text\" name=\"db[{$dbtableName}][tablename]\" value=\"{$dbtableName}\" oldvalue=\"{$dbtableName}\"/>）：<input type=\"text\" name=\"db[{$dbtableName}][comment]\" value=\"{$val['comment']}\"/></legend>";
        // 第二层单表
        // 索引
        $outHtml[] = '<fieldset class="fs-subform fs-subform-Index">';
        $indexCount = count( $val['index'] ) ? count( $val['index'] ) : 0;
        $outHtml[] = '<legend>索引设定' . '(<span class="indexCount">有 '.$indexCount.' 个索引</span>)' . '</legend>';
        if( $val['index'] ){
            foreach( $val['index'] as $keyIndex => $valIndex ){
                $outHtml[] = '<lable>';
                $outHtml[] = '<span title="清空字段属性" class="btn-del">✖</span>';
                $outHtml[] = "<input id=\"{$keyIndex}\" type=\"text\" name=\"db[{$dbtableName}][index][{$keyIndex}]\" value=\"{$valIndex['columns'][0]}\"/>";
                $outHtml[] = '</lable>';
            }
        }
        $outHtml[] = '</fieldset>';
        // 字段信息
        if( $val['columns'] ){
            $outHtml[] = '<fieldset class="fs-subform fs-subform-Columns">';
            $outHtml[] = '<legend>字段设定'.( count($val['columns']) > 0 ? '(有'.count($val['columns']).'个字段)' : '' ).'[ <span class="btn-addcol" title="增加表字段">➕</span> ]</legend>';
            foreach( $val['columns'] as $keyColumns => $valColumns ){
                $indexItem = 0;
                //
                $outHtml[] = '<fieldset class="row fs-subform-item">';
                $outHtml[] = '<legend>'.
                                '[<span class="btn-coldel" title="删除字段">✖</span> <span class="btn-pkey" title="字段是索引" data-key="'.$keyColumns.'">➽</span> ] '.
                                '<span class="showItem">字段：[ '.$keyColumns.' ]</span>';
                $outHtml[] = "（<input class=\"filedName\" type=\"text\" name=\"db[{$dbtableName}][filedName]\" value=\"{$keyColumns}\" oldvalue=\"{$keyColumns}\"/> ".
                                $OpOptionItem[2]."）</legend>";
                //
                $outHtml[] = '<div class="fs-subform-item-frame" style="height: 0px;">';
               
                foreach( $typeDefault as $keyColumnsSub => $valColumnsSub ){
                    if( count($valColumnsSub) > 1 ){
                        $outHtmlSub = array();
                        $outHtmlSub[] = '<div id="O_'.$dbtableName.'_'.$keyColumns.'_'.$indexItem.'">' . "<span class=\"typeTip\">( {$keyColumnsSub} )</span>" . $typeToCName[ $keyColumnsSub ] . $OpOption;
                        $outHtmlSub[] = "<select id=\"{$dbtableName}_{$keyColumns}_{$indexItem}\" name=\"db[{$dbtableName}][columns][{$keyColumns}][{$keyColumnsSub}]\" class=\"Col_{$keyColumnsSub}\">";
                        $isOptG = false;
                        $totalItem = count( $valColumnsSub );
                        $index = 1;
                        // 该字段是否有选择项
                        $isSelect = false;
                        foreach( $valColumnsSub as $keyColumnsSubTmp => $valColumnsSubTmp ){
                            $fieldset = explode( ' | ', $valColumnsSubTmp );
                            if( 'ROWCOL' == $fieldset[0] ){
                                if( $isOptG ){
                                    $outHtmlSub[] = '</optgroup>';
                                }
                                $outHtmlSub[] = '<optgroup label="'.$fieldset[1].'">';
                                $isOptG = true;
                            }else{
                                $outHtmlSub[] = "<option value=\"{$fieldset[0]}\"";
                                // 根据值来判断是否选择状态
                                // 1.是否设置值，没有设置值的，全部默认false
                                // 2.有值，但是检查看看是否有选择，没有选择的话就将值当做选中的选项显示
                                if( isset($valColumns[ $keyColumnsSub ]) ){
                                    if( gettype($valColumns[ $keyColumnsSub ]) == 'boolean' ){
                                        if( $valColumns[ $keyColumnsSub ] == 1 ){
                                            $valColumns[ $keyColumnsSub ] = 'true';
                                        }else{
                                            $valColumns[ $keyColumnsSub ] = 'false';
                                        }
                                    } 
                                    //
                                    if( $fieldset[0]===$valColumns[ $keyColumnsSub ] ){
                                        $isSelect = true;
                                        $outHtmlSub[] = ' selected="selected"';
                                    }                                    
                                }else{
                                    // 使用 - 为了程序处理时将这个字段过滤掉，不输出
                                    if( $fieldset[0] == '-' ) 
                                        $outHtmlSub[] = ' selected="selected"';
                                }
                                $outHtmlSub[] = ($fieldset[1] ? " title=\"{$fieldset[1]}\"" : '').">{$fieldset[0]}</option>";                                
                                if( $isOptG && ( $totalItem == $index ) ) $outHtmlSub[] = '</optgroup>';
                            }
                            $index++;
                        }
                        //
                        if( !$isSelect && !empty($valColumns[ $keyColumnsSub ]) ){
                            if( is_array($valColumns[ $keyColumnsSub ]) ){
                                $kCSTmp = array();
                                foreach( $valColumns[ $keyColumnsSub ] as $kCSkey => $kCSval ){
                                    $kCSTmp[] = "'{$kCSkey}|{$kCSval}'";
                                }
                                $valColumns[ $keyColumnsSub ] = 'custom_enum('.implode( ' , ', $kCSTmp ).')';
                            }
                            $outHtmlSub[] = "<optgroup label=\"默认值\"><option value=\"{$valColumns[ $keyColumnsSub ]}\" selected='selected'>{$valColumns[ $keyColumnsSub ]}</option></optgroup>";                           
                        }
                        //
                        $outHtmlSub[] = '</select>';
                        $outHtmlSub[] = '</div>';
                        $outHtml[] = implode( "\n", $outHtmlSub );
                    }else{
                        if( count($valColumnsSub)==0 ){
                            $outHtml[] = '<div id="O_'.$dbtableName.'_'.$keyColumns.'_'.$indexItem.'">' . 
                                            "<span class=\"typeTip\">( {$keyColumnsSub} )</span>" . 
                                            $typeToCName[ $keyColumnsSub ] . $OpOption . 
                                            "<input id=\"{$dbtableName}_{$keyColumns}_{$indexItem}\" " . 
                                            "type=\"text\" name=\"db[{$dbtableName}][columns][{$keyColumns}][{$keyColumnsSub}]\" " . 
                                            "value=\"{{$valColumns[ $keyColumnsSub ]}}\" class=\"Col_{$keyColumnsSub}\"/></div>";
                        }else{
                            $outHtml[] = '<div id="O_'.$dbtableName.'_'.$keyColumns.'_'.$indexItem.'">' . "<span class=\"typeTip\">( {$keyColumnsSub} )</span>" .
                                            $typeToCName[ $keyColumnsSub ] . $OpOption .
                                            "<input id=\"{$dbtableName}_{$keyColumns}_{$indexItem}\" type=\"text\" " .
                                            "name=\"db[{$dbtableName}][columns][{$keyColumns}][{$keyColumnsSub}]\" " .
                                            "value=\"{$valColumns[ $keyColumnsSub ]}\" class=\"Col_{$keyColumnsSub}\"/></div>";
                        }
                    }
                    $indexItem++;
                }
                $outHtml[] = '</div>';
                $outHtml[] = '</fieldset>';
            }
            //
            $outHtml[] = '</fieldset>';
        }
        //
        $outHtml[] = '</fieldset>';
        echo implode( "\n", $outHtml );
    }
    //
    echo '<fieldset class="fs-subform"><input type="submit" name="submit" value="提交数据"/></fieldset>';
    //
    echo '</form>';
}

// 默认数据模板
function defaultDB( &$db ){
    $db['DEMO']=array (
        'engine'    => 'innodb',
        'version'   => '$Rev: ' . date('Ymd') . ' $',
        'comment'   => app::get('b2c')->_('演示表'),
        /* 字段定义 */
        'columns'   => array (
            'demo_id' =>
            array (
                'type' => 'number',
                'extra' => 'auto_increment',
                'pkey' => true,
                'label' => '主索引',
            )
        ),
        /* 索引 */
        'index'     => array(
            'ind_demo_id' => array( 
                'columns' => array( 
                    0 => 'demo_id'
                )
            )
        ),
    );
}

// 输出字段类型的中文注释
function typeToCN( $type = null, $array_flip = false ){
	$dataTbName = array(
		'engine'			=> '数据库引擎',
		'version'			=> '数据库版本',
		'type'				=> '字段类型',
		'pkey'				=> '主键',
		'extra'				=> '附加',
		'required'			=> '必填',
		'virtual_pkey'		=> '虚拟主键',
		'editable'			=> '可编辑',
		'filtertype'		=> '过滤类型',
		'in_list'			=> '在字段配置区',
		'default_in_list'	=> '默认配置区显示',
		'filterdefault'		=> '默认过滤类型',
		'width'				=> '字段宽度',
		'default'			=> '默认值',
		'label'				=> '标签文字',
		'comment'			=> '功能注释',
		'sdfpath'			=> 'SDF路径',
		'orderby'			=> '是否排序',
		'hidden'			=> '是否隐藏',
		'searchtype'		=> '查询类型',
		'deny_export'		=> '导出',
		'searchable'		=> '是否可查询',
		'order'				=> '显示顺序',
		'depend_col'		=> '依赖列',
		'match'				=> '正则匹配',
	);
    //
    if( $array_flip ){
        $dataTbName = array_flip($dataTbName);
    }    
    return ( $type==null ? $dataTbName : $dataTbName[ $type ] );
}

function typeDefault(){
    /*
    BIGINT[(M)] [UNSIGNED]                  -9223372036854775808~9223372036854775807 / 0~18446744073709551615
    INT[(M)] [UNSIGNED]                     -2147483648~2147483647 / 0~4294967295
    SMALLINT[(M)] [UNSIGNED]                -32768~32767 / 0~65535.
    TINYINT[(M)] [UNSIGNED]                 -128~127 / 0~255
    FLOAT[(M,D)] [UNSIGNED]                 -3.402823466E+38~-1.175494351E-38, 0, 1.175494351E-38~3.402823466E+38
    MEDIUMINT[(M)] [UNSIGNED]               -8388608~8388607 / 0~16777215
    DECIMAL[(M[,D])] [UNSIGNED]             (M) Max 65.(D) Max 30
    BOOL, BOOLEAN                           TINYINT(1). 0 == false. !0 == true
    
    VARCHAR(M)                              M is 0~65,535
    CHAR[(M)]                               M is 0~255
    LONGTEXT                                最大长度为4,294,967,295 or 4GB (232 − 1)个字符数
    ENUM('value1','value2',...)             最大不重复元素为65,535个
    TEXT[(M)]                               最大长度为65,535 (216 − 1)个字符数，指定M值则使用小型
    TIME[(fsp)]                             '-838:59:59.000000' to '838:59:59.000000'，以'HH:MM:SS[.fraction]'格式显示
    DATE                                    '1000-01-01' to '9999-12-31'，以'YYYY-MM-DD'格式显示
    DATETIME[(fsp)]                         '1000-01-01 00:00:00.000000' to '9999-12-31 23:59:59.999999'，以'YYYY-MM-DD HH:MM:SS[.fraction]'格式显示
    TIMESTAMP[(fsp)]                        '1970-01-01 00:00:01.000000' UTC to '2038-01-19 03:14:07.999999' UTC    
    -
    */
    $tip_1 = '语法: ';
    $tip_2 = '范围: ';
    $tip[0]  = ' | ' . "{$tip_1}BIGINT[(M)] [UNSIGNED]\n{$tip_2}-9223372036854775808~9223372036854775807 / 0~18446744073709551615";
    $tip[1]  = ' | ' . "{$tip_1}INT[(M)] [UNSIGNED]\n{$tip_2}-2147483648~2147483647 / 0~4294967295";
    $tip[2]  = ' | ' . "{$tip_1}FLOAT[(M,D)] [UNSIGNED]\n{$tip_2}-3.402823466E+38~-1.175494351E-38, 0, 1.175494351E-38~3.402823466E+38";
    $tip[3]  = ' | ' . "{$tip_1}SMALLINT[(M)] [UNSIGNED]\n{$tip_2}-32768~32767 / 0~65535.";
    $tip[4]  = ' | ' . "{$tip_1}MEDIUMINT[(M)] [UNSIGNED]\n{$tip_2}-8388608~8388607 / 0~16777215";
    $tip[5]  = ' | ' . "{$tip_1}TINYINT[(M)] [UNSIGNED]\n{$tip_2}-128~127 / 0~255";
    $tip[6]  = ' | ' . "{$tip_1}DECIMAL[(M[,D])] [UNSIGNED]\n{$tip_2}(M) Max 65.(D) Max 30";
    $tip[7]  = ' | ' . "{$tip_1}VARCHAR(M)\n{$tip_2}M is 0~65,535";
    $tip[8]  = ' | ' . "{$tip_1}CHAR[(M)]\n{$tip_2}M is 0~255";
    $tip[9]  = ' | ' . "{$tip_1}TEXT[(M)]\n{$tip_2}最大长度为65,535 (216 − 1)个字符数，指定M值则使用小型";
    $tip[10] = ' | ' . "{$tip_1}TIME[(fsp)]\n{$tip_2}'-838:59:59.000000' to '838:59:59.000000'，以'HH:MM:SS[.fraction]'格式显示";
    $tip[11] = ' | ' . "{$tip_1}LONGTEXT\n{$tip_2}最大长度为4,294,967,295 or 4GB (232 − 1)个字符数";
    $tip[12] = ' | ' . "{$tip_1}ENUM('value1','value2',...){$tip_2}最大不重复元素为65,535个";
    $tip[13] = ' | ' . "{$tip_1}DATE{$tip_2}'1000-01-01' to '9999-12-31'，以'YYYY-MM-DD'格式显示";
    //
	$dataTb = array(
		"type" => array(
            'ROWCOL | 字符串类型', 
            '-',
            "varchar(255)" . $tip[7],
            "char(255)" . $tip[8],
            "text" . $tip[9],
            "time" . $tip[10],
            "longtext" . $tip[11],
            "enum('false', 'true')" . $tip[12],
            "date" . $tip[13],
            'ROWCOL | 数字类型', 
            'bigint(20)' . $tip[0],
            "bigint(20) unsigned" . $tip[0],
            "int(10)" . $tip[1],
            "int(10) unsigned" . $tip[1],
            'integer(10)' . $tip[1],
            'integer(10) unsigned' . $tip[1],
            "float(10,2)" . $tip[2],
            "float(10,2) unsigned" . $tip[2],
            "smallint(5)" . $tip[3],
            "smallint(5) unsigned" . $tip[3],
            "mediumint(8)" . $tip[4],
            "mediumint(8) unsigned" . $tip[4],
            "tinyint(3)" . $tip[5],
            "tinyint(3) unsigned" . $tip[5],
            "decimal(20,3)" . $tip[6],
            "decimal(20,3) unsigned" . $tip[6],
            'ROWCOL | 自定义类型', 
            "money | decimal(20,3)",
            "number | mediumint unsigned",
            "region | varchar(255)",
            "table:members@pam:member_id | table:表名@APP名:字段名",
            "bool | enum('true','false')",
            "intbool | enum('0','1')",
            "tinybool | enum('Y','N')",
            "last_modify | integer(10) unsigned",
            "serialize | longtext",
        ),
		"pkey" => array('-',"true","false",),
		"extra" => array('-',"auto_increment"),			
		"required" => array('-',"false","true",),
		"virtual_pkey" => array('-',"false","true",),
		"editable" =>  array('-',"false","true",),
		"filtertype" => array('-',"yes","normal","number","time","custom","bool",),
		"in_list" => array('-',"false","true",),
		"default_in_list" => array('-',"false","true",),
		"filterdefault" => array('-',"false","true",),
		"width" => '-',
		"default" => '-',
		"label" => '-',
		"comment" => '-',
		"sdfpath" => array('-',"product[default]/price/cost/price",),
		"orderby" => array('-',"false","true",),
		"hidden" => array('-',"false","true",),
		"searchtype" => array('-',"has","tequal","head",),
		"deny_export" => array('-',"false","true",),
		"searchable" => array('-',"false","true",),
		"order" => array('-','1',),
		"depend_col" => array('-',"marketable:true:now",),
		"match" => array('-',"0-9\.+",),
	);
    return $dataTb;
}

?>
<!DOCTYPE html>
<html lang="zh-cn">
    <head>
        <title>APP DA Table结构生成器</title>
        <meta charset="utf-8"/>
        <script src="http://apps.bdimg.com/libs/jquery/1.9.1/jquery.min.js"></script>
        <script src="md5.js"></script>
        <style>
        *{
            font-size:12px;
            margin:0;
            pading:0;
        }
		textarea {
			border: 1px solid #c5c5c5;
			font-size: 12px;
			margin: 10px;
			width: 96%;
		}
        legend{
            cursor:pointer;
        }
        lable {
            float: left;
        }
        .fs-form{
            margin: 20px 5px;
            
        }
        .fs-subform{
            border-left:none;
            border-right:none;
            border-bottom:none;
            margin:5px;
        }
        /*.row {
          border: 1px dotted #c5c5c5;
          margin: 5px 0;
          padding: 0 10px;
        }*/
        .row {
            border: 1px dotted #c5c5c5;
            float: left;
            margin: 2px;
            overflow: hidden;
            padding: 0 10px;
            width: 36%;
        }
        .row .fs-subform-item-frame{
            display:none;
        }
        .row div {
          display: block;
          font-size: 15px;
          margin: 5px;
          text-align: right;
          width: 99%;
        }
        .row div input,
        .row div select
        {
          float: right;
          width: 50%;
          min-width:50%;
          margin-left:5px;
        }
        .row div select
        {
          width: 51%;
          min-width:51%;
        }
        span.btn-edit,
        span.btn-del,
        span.btn-pkey,
        span.btn-ts,
        span.btn-coldel
        {
          color: #c5c5c5;
          cursor: pointer;
          font-size: 15px;
          margin: 0 0 0 5px;
        }
        span.btn-ts {
            font-size: 20px;
            line-height: 16px;
        }
        span.btn-pkey-cur{
            color:#9f9f9f;
        }
        span.btn-edit:hover,
        span.btn-del:hover,
        span.btn-pkey:hover,
        span.btn-ts:hover,
        span.btn-coldel:hover
        {
            color:#000;
        }
        .fs-subform-item-frame{
            overflow:hidden;
        }
        .fs-subform-Index .btn-del{
            margin:0 3px;
        }
        .fs-subform-Index input {
            border: 0 none;
            color: #c5c5c5;
            width: auto;
        }
        .typeTip{
            color:#c5c5c5;
        }
        .filedName {
            -moz-border-bottom-colors: none;
            -moz-border-left-colors: none;
            -moz-border-right-colors: none;
            -moz-border-top-colors: none;
            border-color: -moz-use-text-color -moz-use-text-color #c5c5c5;
            border-image: none;
            border-style: none none solid;
            border-width: 0 0 1px;
            color: #c5c5c5;
            text-align: left;
        }
        /**/
        .controller {
            border: 1px solid #c6c6c6;
            color: #ff9ce0;
            cursor: pointer;
            margin: 20px;
            padding: 20px;
        }
        .controller span {
            border-bottom: 1px dotted;
            color: #c4c4c4;
            margin: 5px;
            padding: 5px;
        }

        </style>
        <script type="text/javascript">
        ;
        $( function(){
            // 字段详细每个设置的操作
            $( '.fs-subform-item div span' ).click( function(){
                var nowObj = $(this),
                    oldObj = nowObj.nextAll('select,input'),
                    oldVal = oldObj.val();
                if( nowObj.hasClass('btn-edit') === true ){
                    if( oldObj.is('input') ){
                        oldObj.val( '-' );
                    }else if( oldObj.is('select') ){
                        nowObj.nextAll('.btn-del').after( '<input type="text" name="'+oldObj.attr( 'name' )+'" value="'+oldVal+'"/>' );
                        oldObj.remove();                        
                    }
                }else if( nowObj.hasClass('btn-del') === true ){
                    if( oldObj.is('input') ){
                        oldObj.val( '-' );
                    }else if( oldObj.is('select') ){
                        nowObj.after( '<input type="text" name="'+oldObj.attr( 'name' )+'" value="-"/>' );
                        oldObj.remove();
                    }
                }else if( nowObj.hasClass('btn-ts') === true ){
                    // 检测不是很准确
                    var isCn = ( new RegExp( /[\u4E00-\u9FA5]/g ) ).test( oldObj.val() );
                    if( isCn ){
                        getTranstCn( oldObj.val(), oldObj );
                    }
                }
            } );
            // 展示字段详细
            $( 'fieldset.fs-subform-item legend span.showItem' ).click( function(){
                var fsFrame = $(this).parent('legend').next('.fs-subform-item-frame');
                if( parseInt(fsFrame.css('height').replace('px','')) == 0 ){
                    fsFrame.css({"height":'auto',"display":'block'});
                }else{
                    fsFrame.css({"height":'0',"display":'none'});
                }
            } );
            // 添加索引值
            $( 'fieldset.fs-subform-item legend span.btn-pkey' ).click( function(){
                var key = $(this).attr( 'data-key' ),
                    fsRoot = $(this).parents( '.fs-form' ),
                    tableName = fsRoot.attr( 'id' ) || (new Date).getTime(),
                    fsIndex = fsRoot.children( '.fs-subform-Index' ),
                    indexCount = fsIndex.find( 'legend .indexCount' );
                if( fsIndex.find('input[name="db['+tableName+'][index][ind_'+key+']"]').length == 0 ){
                    var tmpString = '';
                    tmpString += '<lable>';
                    tmpString += '<span title="清空字段属性" class="btn-del">✖</span>';
                    tmpString += '<input id="ind_'+key+'" name="db['+tableName+'][index][ind_'+key+']" value="'+key+'" type="text"/>';
                    tmpString += '</lable>';
                    fsIndex.append( tmpString );
                }
                indexCount.html( indexCount.html().replace( /([0-9]{1,})/g, fsIndex.find('input').length ) );
            } );
            // 索引值删除
            $( '.fs-subform-Index' ).delegate( '.btn-del', 'click', function(){
                var fsIndex = $(this).parents( '.fs-subform-Index' ),
                    indexCount = fsIndex.find( 'legend .indexCount' );
                $(this).parents( 'lable' ).remove();
                indexCount.html( indexCount.html().replace( /([0-9]{1,})/g, fsIndex.find('input').length ) );
            } );
            // 表名修改
            $( 'input.tblName' ).change( function(){
                var obj = $(this),
                    tblName = obj.val(),
                    oldvalue = obj.attr( 'oldvalue' );
                obj.attr( 'oldvalue', tblName );
                obj.parents( '.fs-form' ).find( 'select,input' ).each( function(idx,el){
                    var item = $(el),
                        newVal = item.attr( 'name' ).replace( '['+oldvalue+']','['+tblName+']');
                    item.attr( 'name', newVal );
                } );
            } );
            // 字段名修改
            $( 'input.filedName' ).change( function(){
                var obj = $(this),
                    pobj = obj.prev( '.showItem' ),
                    pobjpkey = pobj.prev( '.btn-pkey' ),
                    filedName = obj.val(),
                    oldvalue = obj.attr( 'oldvalue' );
                if( filedName == '' ){
                    alert( '不能为空噢' );
                    return false;
                }
                obj.attr( 'oldvalue', filedName );
                pobj.html( pobj.html().replace( oldvalue, filedName ) );
                pobjpkey.attr( 'data-key', filedName );
                obj.parents( '.fs-subform-item' ).find( '.fs-subform-item-frame' ).find( 'select,input' ).each( function(idx,el){
                    var item = $(el),
                        newVal = item.attr( 'name' ).replace( '[columns]['+oldvalue+']','[columns]['+filedName+']');
                    item.attr( 'name', newVal );
                } );
            } );
            // 顶部控制面板
            $( '.controller span' ).click( function(){
                switch( true ){
                    case $(this).hasClass( 'btn-addtbstu' ):
                        var allFieldsets = $( 'form fieldset.fs-form' );
                        var newObj = allFieldsets.first().clone( true );
                        newObj.find('select,input').val('-');
                        newObj.insertAfter( allFieldsets.last() );
                    break;
                    default:
                    break;
                }
            } );
            // 每表内的字段操作
            $( '.fs-subform-Columns' ).delegate( '.btn-addcol', 'click', function(){
                var allFieldsetsCol = $( this ).parents( '.fs-subform-Columns' ),
                    ColCount = allFieldsetsCol.find( 'legend' );
                    allFieldsets = allFieldsetsCol.find( '.fs-subform-item' );
                var newObj = allFieldsets.first().clone( true );
                
                newObj.find('select,input').val('-');
                
                newObj.insertAfter( allFieldsets.last() );
                var icount = ColCount.first().html().replace( /([0-9]{1,})/g, allFieldsetsCol.find( '.fs-subform-item' ).length );
                ColCount.first().html( icount );
            } );
            // 每个字段的删除操作
            $( '.fs-subform-Columns' ).delegate( '.fs-subform-item .btn-coldel', 'click', function(){
                var allFieldsetsCol = $( this ).parents( '.fs-subform-Columns' ),
                    ColCount = allFieldsetsCol.find( 'legend' );
                if( allFieldsetsCol.find( '.fs-subform-item' ).length > 1){
                    $(this).parents( '.fs-subform-item' ).remove();
                }else{
                    alert( '必须保留一个字段' );
                }
                var icount = ColCount.first().html().replace( /([0-9]{1,})/g, allFieldsetsCol.find( '.fs-subform-item' ).length );
                ColCount.first().html( icount );
            } );
            // 字段名为中文点击翻译
            $( '.fs-subform-item legend .btn-ts' ).click( function(){
                var nowObj = $(this),
                    oldObj = nowObj.prev('.filedName'),
                    oldVal = oldObj.val();
                // 检测不是很准确
                var isCn = ( new RegExp( /[\u4E00-\u9FA5]/g ) ).test( oldVal );
                if( isCn ){
                    getTranstCn( oldVal, oldObj, null, null ,'change' );
                    // Col_label,Col_comment
                    var fsi = nowObj.parents( '.fs-subform-item' );
                    fsi.find( '.fs-subform-item-frame .Col_label' ).val( oldVal );
                    fsi.find( '.fs-subform-item-frame .Col_comment' ).val( oldVal );
                }
            } );
            //
        } );
        
        // 获取英文翻译
        function getTranstCn( query, noticObj, appID, key, triggerEvent ){
            var appid   = appID || '20160802000026138';
            var key     = key   || '7CbHXl64Bi9pHXA_nLb1';
            var noticObj= noticObj || false;
            var triggerEvent = triggerEvent || false;
            var salt = (new Date).getTime();
            var from = 'zh';
            var to = 'en';
            var str1 = appid + query + salt +key;
            var sign = MD5(str1);
            //
            $.ajax({
                url: 'http://api.fanyi.baidu.com/api/trans/vip/translate',
                type: 'get',
                dataType: 'jsonp',
                data: {
                    q: query,
                    appid: appid,
                    salt: salt,
                    from: from,
                    to: to,
                    sign: sign
                },
                success: function (data) {
                    var retVal = data.trans_result[0].dst.toLowerCase().replace( /\s/g, '_' );
                    if( false === noticObj ){
                        console.log( retVal );
                    }else{
                        console.log(data);
                        switch( typeof(noticObj) ){
                            case 'string':
                                var preO = noticObj.charAt(0);
                                if( (preO!='#') && (preO !='.') ) noticObj = '#'+noticObj;
                                noticObj = $( noticObj );
                                noticObj.val( retVal );
                            break;
                            default:
                                noticObj.val( retVal );
                            break;
                        }
                        if( triggerEvent !== false){
                            noticObj.trigger( triggerEvent );
                        }
                    }
                } 
            });
        }
        //
        </script>
    </head>
    <body>
        <div class="controller">
            <span class="btn-addtbstu">增加表结构</span>
        </div>
        
    <?php gentHTML( $db ); ?>
    
    </body>
</html>