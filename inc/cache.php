<?php
function icl_cache_get($key){
    $icl_cache = get_option('_icl_cache');
    if(isset($icl_cache[$key])){
        return $icl_cache[$key];
    }else{
        return false;
    }
}  

function icl_cache_set($key, $value=null){
    $icl_cache = get_option('_icl_cache');
    if(false === $icl_cache){
        delete_option('_icl_cache');
    }
    if(!is_null($value)){
        $icl_cache[$key] = $value;    
    }else{
        if(isset($icl_cache[$key])){
            unset($icl_cache[$key]);
        }        
    }
    update_option('_icl_cache', $icl_cache);
}

function icl_cache_clear($key){
    delete_option('_icl_cache');
}

define('ICL_DISABLE_CACHE', false);

class icl_cache{
   
    private $data;
    
    function __construct($name = "", $cache_to_option = false){
        $this->data = array();
        $this->name = $name;
        $this->cache_to_option = $cache_to_option;
        
        if ($cache_to_option) {
            $this->data = icl_cache_get($name.'_cache_class');
            if ($this->data == false){
                $this->data = array();
            }
        }
    }
    
    function get($key) {
        if(ICL_DISABLE_CACHE){
            return null;
        }
        return $this->data[$key];
    }
    
    function has_key($key){
        if(ICL_DISABLE_CACHE){
            return false;
        }
        return array_key_exists($key, (array)$this->data);
    }
    
    function set($key, $value) {
        if(ICL_DISABLE_CACHE){
            return;
        }
        $this->data[$key] = $value;
        if ($this->cache_to_option) {
            icl_cache_set($this->name.'_cache_class', $this->data);
        }
    }
    
    function clear() {
        $this->data = array();
        if ($this->cache_to_option) {
            icl_cache_clear($this->name.'_cache_class');
        }
    }
}

?>