<?php
// creates a serialized array from the languages.csv file and stores it into the 'res' folder
// disabled

return;
 
//
$fh = fopen(ICL_PLUGIN_PATH . '/res/languages.csv', 'r');
$idx = 0;
while($data = fgetcsv($fh)){
    if($idx == 0){
        foreach($data as $k=>$v){
            if($k < 3) continue;
            $lang_idxs[] = $v; 
        }
    }else{
        foreach($data as $k=>$v){
            if($k < 2) continue;                    
            if($k == 2){
                $langs_names[$lang_idxs[$idx-1]]['major'] = intval($v);
                continue;
            }
            $langs_names[$lang_idxs[$idx-1]]['tr'][$lang_idxs[$k-3]] = $v; 
        }
    }
    $idx++;
}

$fh = fopen(ICL_PLUGIN_PATH . '/res/languages.csv.php','w') or die('Can\'t open file');
fwrite($fh,'<?php $__icl_lang_names = \''.serialize($langs_names).'\'; ?>');  
?>