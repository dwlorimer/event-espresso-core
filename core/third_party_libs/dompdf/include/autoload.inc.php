<?php

 

function DOMPDF_autoload($class) {
  $filename = DOMPDF_INC_DIR . "/" . mb_strtolower($class) . ".cls.php";
  
  if ( is_file($filename) )
    require_once($filename);
}

if ( function_exists("spl_autoload_register") ) {
  $autoload = "DOMPDF_autoload";
  $funcs = spl_autoload_functions();
  
    if ( !DOMPDF_AUTOLOAD_PREPEND || $funcs === false ) {
    spl_autoload_register($autoload); 
  }
  
    else if ( PHP_VERSION_ID >= 50300 ) {
    spl_autoload_register($autoload, true, true); 
  }
  
  else {
        $compat = (PHP_VERSION_ID <= 50102 && PHP_VERSION_ID >= 50100);
              
    foreach ($funcs as $func) { 
      if (is_array($func)) { 
                        $reflector = new ReflectionMethod($func[0], $func[1]); 
        if (!$reflector->isStatic()) { 
          throw new Exception('This function is not compatible with non-static object methods due to PHP Bug #44144.'); 
        }
        
                        if ($compat) $func = implode('::', $func); 
      }
      
      spl_autoload_unregister($func); 
    }
    
        spl_autoload_register($autoload); 
    
        foreach ($funcs as $func) { 
      spl_autoload_register($func); 
    }
    
        if ( function_exists("__autoload") ) {
      spl_autoload_register("__autoload");
    }
  }
}

else if ( !function_exists("__autoload") ) {
  
  function __autoload($class) {
    DOMPDF_autoload($class);
  }
}
