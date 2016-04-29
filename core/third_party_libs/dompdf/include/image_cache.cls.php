<?php



class Image_Cache {

  
  static protected $_cache = array();
  
  
  public static $broken_image;

  
  static function resolve_url($url, $proto, $host, $base_path) {
    $parsed_url = explode_url($url);
    $message = null;

    $remote = ($proto && $proto !== "file://") || ($parsed_url['protocol'] != "");
    
    $datauri = strpos($parsed_url['protocol'], "data:") === 0;

    try {
      
            if ( !DOMPDF_ENABLE_REMOTE && $remote && !$datauri ) {
        throw new DOMPDF_Image_Exception("DOMPDF_ENABLE_REMOTE is set to FALSE");
      } 
      
            else if ( DOMPDF_ENABLE_REMOTE && $remote || $datauri ) {
                $full_url = build_url($proto, $host, $base_path, $url);
  
                if ( isset(self::$_cache[$full_url]) ) {
          $resolved_url = self::$_cache[$full_url];
        }
        
                else {
          $resolved_url = tempnam(DOMPDF_TEMP_DIR, "ca_dompdf_img_");
  
          if ($datauri) {
            if ($parsed_data_uri = parse_data_uri($url)) {
              $image = $parsed_data_uri['data'];
            }
          }
          else {
            $old_err = set_error_handler("record_warnings");
            $image = file_get_contents($full_url);
            restore_error_handler();
          }
  
                    if ( strlen($image) == 0 ) {
            $msg = ($datauri ? "Data-URI could not be parsed" : "Image not found");
            throw new DOMPDF_Image_Exception($msg);
          }
          
                    else {
                                                                        file_put_contents($resolved_url, $image);
          }
        }
      }
      
            else {
        $resolved_url = build_url($proto, $host, $base_path, $url);
      }
  
  
            if ( !is_readable($resolved_url) || !filesize($resolved_url) ) {
        throw new DOMPDF_Image_Exception("Image not readable or empty");
      }
      
            else {
        list($width, $height, $type) = dompdf_getimagesize($resolved_url);
        
                if ( $width && $height && in_array($type, array(IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_BMP)) ) {
                              if ( DOMPDF_ENABLE_REMOTE && $remote ) {
            self::$_cache[$full_url] = $resolved_url;
          }
        }
        
                else {
          throw new DOMPDF_Image_Exception("Image type unknown");
          unlink($resolved_url);
        }
      }
    }
    catch(DOMPDF_Image_Exception $e) {
      $resolved_url = self::$broken_image;
      $type = IMAGETYPE_PNG;
      $message = $e->getMessage()." \n $url";
    }

    return array($resolved_url, $type, $message);
  }

  
  static function clear() {
    if ( empty(self::$_cache) || DEBUGKEEPTEMP ) return;
    
    foreach ( self::$_cache as $file ) {
      if (DEBUGPNG) print "[clear unlink $file]";
      unlink($file);
    }
  }
  
  static function detect_type($file) {
    list($width, $height, $type) = dompdf_getimagesize($file);
    return $type;
  }
  
  static function type_to_ext($type) {
    $image_types = array(
      IMAGETYPE_GIF  => "gif",
      IMAGETYPE_PNG  => "png",
      IMAGETYPE_JPEG => "jpeg",
      IMAGETYPE_BMP  => "bmp",
    );
    
    return (isset($image_types[$type]) ? $image_types[$type] : null);
  }
  
  static function is_broken($url) {
    return $url === self::$broken_image;
  }
}

Image_Cache::$broken_image = DOMPDF_LIB_DIR . "/res/broken_image.png";
