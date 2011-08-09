<?php
/*
if ( !function_exists('sys_get_temp_dir')) {
  function sys_get_temp_dir() {
      if( $temp=getenv('TMP') )        return $temp;
      if( $temp=getenv('TEMP') )        return $temp;
      if( $temp=getenv('TMPDIR') )    return $temp;
      $temp=tempnam(__FILE__,'');
      if (file_exists($temp)) {
          unlink($temp);
          return dirname($temp);
      }
      return null;
  }
}
*/  
function icl_troubleshooting_dumpdb(){
    ini_set('memory_limit','128M');

    $dump = _icl_ts_mysqldump(DB_NAME);
    $gzdump = gzencode($dump, 9);
    
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=" . DB_NAME . ".sql.gz");
    //header("Content-Encoding: gzip");
    header("Content-Length: ". strlen($gzdump));
    
    echo $gzdump;
}




function _icl_ts_mysqldump($mysql_database)
{
    global $wpdb;
    $upload_folder = wp_upload_dir();
    $dump_tmp_file = $upload_folder['path'] . '/' . '__icl_mysqldump.sql';
    
    $fp = @fopen($dump_tmp_file, 'w');        
    if(!$fp){
        $fp = fopen('php://output', 'w');        
        ob_start();
    }
    
    $sql="SHOW TABLES LIKE '".str_replace('_','\_',$wpdb->prefix)."%';";
    
    $result= mysql_query($sql);
    if( $result)
    {
        while( $row= mysql_fetch_row($result))
        {
            //_icl_ts_mysqldump_table_structure($row[0]);
            //_icl_ts_mysqldump_table_data($row[0]);
            _icl_ts_backup_table($row[0], 0, $fp);            
        }
    }
    else
    {
        echo "/* no tables in $mysql_database */\n";
    }
    mysql_free_result($result);
    fclose ($fp);
    
    
    if(file_exists($dump_tmp_file)){
        $data = file_get_contents($dump_tmp_file);
        @unlink($dump_tmp_file);    
    }else{
        $data = ob_get_contents();
        ob_end_clean();
    }
    
    return $data ;
}

/*
function _icl_ts_mysqldump_table_structure($table)
{
    echo "DROP TABLE IF EXISTS `$table`;\n\n";
        
    $sql="show create table `$table`; ";
    $result=mysql_query($sql);
    if( $result)
    {
        if($row= mysql_fetch_assoc($result))
        {
            echo $row['Create Table'].";\n\n";
        }
    }
    mysql_free_result($result);

}
*/

/*
function _icl_ts_mysqldump_table_data($table)
{
    
    $sql="select * from `$table`;";
    $result=mysql_query($sql);
    if( $result)
    {
        $num_rows= mysql_num_rows($result);
        $num_fields= mysql_num_fields($result);
        
        if( $num_rows > 0)
        {
            $field_type=array();
            $i=0;
            while( $i < $num_fields)
            {
                $meta= mysql_fetch_field($result, $i);
                array_push($field_type, $meta->type);
                $i++;
            }
            
            echo "INSERT INTO `$table` VALUES\n";
            $index=0;
            while( $row= mysql_fetch_row($result))
            {
                echo "(";
                for( $i=0; $i < $num_fields; $i++)
                {
                    if( is_null( $row[$i]))
                        echo "null";
                    else
                    {
                        switch( $field_type[$i])
                        {
                            case 'int':
                                echo $row[$i];
                                break;
                            case 'string':
                            case 'blob' :
                            default:
                                echo "'".mysql_real_escape_string($row[$i])."'";
                                
                        }
                    }
                    if( $i < $num_fields-1)
                        echo ",";
                }
                echo ")";
                
                if( $index < $num_rows-1)
                    echo ",";
                else
                    echo ";";
                echo "\n";
                
                $index++;
            }
        }
    }
    mysql_free_result($result);
    echo "\n";
}
*/

if ( ! defined('ROWS_PER_SEGMENT') ) define('ROWS_PER_SEGMENT', 100);

function _icl_ts_stow($query_line, $fp) {
    if(! @fwrite($fp, $query_line,strlen($query_line)))
        die(__('Error writing query:','sitepress') . '  ' . $query_line);
}
 
function _icl_ts_backquote($a_name) {
    if (!empty($a_name) && $a_name != '*') {
        if (is_array($a_name)) {
            $result = array();
            reset($a_name);
            while(list($key, $val) = each($a_name)) 
                $result[$key] = '`' . $val . '`';
            return $result;
        } else {
            return '`' . $a_name . '`';
        }
    } else {
        return $a_name;
    }
} 
      
function _icl_ts_backup_table($table, $segment = 'none', $fp) {
        global $wpdb;

        $table_structure = $wpdb->get_results("DESCRIBE $table");        
        if(($segment == 'none') || ($segment == 0)) {
            _icl_ts_stow("\n\n", $fp);
            _icl_ts_stow("DROP TABLE IF EXISTS " . _icl_ts_backquote($table) . ";\n", $fp);
            // Table structure
            _icl_ts_stow("\n\n", $fp);
            $create_table = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
            _icl_ts_stow($create_table[0][1] . ' ;', $fp);
            _icl_ts_stow("\n\n", $fp);
        }
        
        if(($segment == 'none') || ($segment >= 0)) {
            $defs = array();
            $ints = array();
            foreach ($table_structure as $struct) {
                if ( (0 === strpos($struct->Type, 'tinyint')) ||
                    (0 === strpos(strtolower($struct->Type), 'smallint')) ||
                    (0 === strpos(strtolower($struct->Type), 'mediumint')) ||
                    (0 === strpos(strtolower($struct->Type), 'int')) ||
                    (0 === strpos(strtolower($struct->Type), 'bigint')) ) {
                        $defs[strtolower($struct->Field)] = ( null === $struct->Default ) ? 'NULL' : $struct->Default;
                        $ints[strtolower($struct->Field)] = "1";
                }
            }
            
            
            // Batch by $row_inc
            
            if($segment == 'none') {
                $row_start = 0;
                $row_inc = ROWS_PER_SEGMENT;
            } else {
                $row_start = $segment * ROWS_PER_SEGMENT;
                $row_inc = ROWS_PER_SEGMENT;
            }
            
            do {    
                $table_data = $wpdb->get_results("SELECT * FROM $table LIMIT {$row_start}, {$row_inc}", ARRAY_A);

                $entries = 'INSERT INTO ' . _icl_ts_backquote($table) . ' VALUES (';    
                //    \x08\\x09, not required
                $search = array("\x00", "\x0a", "\x0d", "\x1a");
                $replace = array('\0', '\n', '\r', '\Z');
                if($table_data) {
                    foreach ($table_data as $row) {
                        $values = array();
                        foreach ($row as $key => $value) {
                            if ($ints[strtolower($key)]) {
                                // make sure there are no blank spots in the insert syntax,
                                // yet try to avoid quotation marks around integers
                                $value = ( null === $value || '' === $value) ? $defs[strtolower($key)] : $value;
                                $values[] = ( '' === $value ) ? "''" : $value;
                            } else {
                                $values[] = "'" . str_replace($search, $replace, $wpdb->escape($value)) . "'";
                            }
                        }
                        _icl_ts_stow(" \n" . $entries . implode(', ', $values) . ');', $fp);
                    }
                    $row_start += $row_inc;
                }
            } while((count($table_data) > 0) and ($segment=='none'));
        }
        
        if(($segment == 'none') || ($segment < 0)) {
            // Create footer/closing comment in SQL-file
            _icl_ts_stow("\n", $fp);
        }
    } // end backup_table()  
?>
