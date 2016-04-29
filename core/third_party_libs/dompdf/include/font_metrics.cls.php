<?php


require_once DOMPDF_LIB_DIR . "/class.pdf.php";
require_once DOMPDF_LIB_DIR . "/php-font-lib/classes/font.cls.php";


if (!defined("__DOMPDF_FONT_CACHE_FILE")) {
  if (file_exists(DOMPDF_FONT_DIR . "dompdf_font_family_cache")) {
    define('__DOMPDF_FONT_CACHE_FILE', DOMPDF_FONT_DIR . "dompdf_font_family_cache");
  } else {
    define('__DOMPDF_FONT_CACHE_FILE', DOMPDF_FONT_DIR . "dompdf_font_family_cache.dist.php");
  }
}


class Font_Metrics {

  
  const CACHE_FILE = __DOMPDF_FONT_CACHE_FILE;
  
  
  static protected $_pdf = null;

  
  static protected $_font_lookup = array();
  
  
  
  static function init(Canvas $canvas = null) {
    if (!self::$_pdf) {
      if (!$canvas) {
        $canvas = Canvas_Factory::get_instance();
      }
      
      self::$_pdf = $canvas;
    }
  }

  
  static function get_text_width($text, $font, $size, $word_spacing = 0, $char_spacing = 0) {
        
        static $cache = array();
    
    if ( $text === "" ) {
      return 0;
    }
    
        $use_cache = !isset($text[50]);     
    $key = "$font/$size/$word_spacing/$char_spacing";
    
    if ( $use_cache && isset($cache[$key][$text]) ) {
      return $cache[$key]["$text"];
    }
    
    $width = self::$_pdf->get_text_width($text, $font, $size, $word_spacing, $char_spacing);
    
    if ( $use_cache ) {
      $cache[$key][$text] = $width;
    }
    
    return $width;
  }

  
  static function get_font_height($font, $size) {
    return self::$_pdf->get_font_height($font, $size);
  }

  
  static function get_font($family, $subtype = "normal") {

    

    if ( $family ) {
      $family = str_replace( array("'", '"'), "", mb_strtolower($family));
      $subtype = mb_strtolower($subtype);

      if ( isset(self::$_font_lookup[$family][$subtype]) ) {
        return self::$_font_lookup[$family][$subtype];
      }
      return null;
    }

    $family = DOMPDF_DEFAULT_FONT;

    if ( isset(self::$_font_lookup[$family][$subtype]) ) {
      return self::$_font_lookup[$family][$subtype];
    }

    foreach ( self::$_font_lookup[$family] as $sub => $font ) {
      if (strpos($subtype, $sub) !== false) {
        return $font;
      }
    }

    if ($subtype !== "normal") {
      foreach ( self::$_font_lookup[$family] as $sub => $font ) {
        if ($sub !== "normal") {
          return $font;
        }
      }
    }

    $subtype = "normal";

    if ( isset(self::$_font_lookup[$family][$subtype]) ) {
      return self::$_font_lookup[$family][$subtype];
    }
    return null;
  }
  
  static function get_family($family) {
    $family = str_replace( array("'", '"'), "", mb_strtolower($family));
    
    if ( isset(self::$_font_lookup[$family]) ) {
      return self::$_font_lookup[$family];
    }
    
    return null;
  }

  
  static function save_font_families() {
        $cache_data = var_export(self::$_font_lookup, true);
    $cache_data = str_replace('\''.DOMPDF_FONT_DIR , 'DOMPDF_FONT_DIR . \'' , $cache_data);
    $cache_data = "<"."?php return $cache_data ?".">";
    file_put_contents(self::CACHE_FILE, $cache_data);
  }

  
  static function load_font_families() {
    if ( !is_readable(self::CACHE_FILE) )
      return;

    self::$_font_lookup = require_once(self::CACHE_FILE);
    
        if ( self::$_font_lookup === 1 ) {
      $cache_data = file_get_contents(self::CACHE_FILE);
      file_put_contents(self::CACHE_FILE, "<"."?php return $cache_data ?".">");
      self::$_font_lookup = require_once(self::CACHE_FILE);
    }
  }
  
  static function get_type($type) {
    if (preg_match("/bold/i", $type)) {
      if (preg_match("/italic|oblique/i", $type)) {
        $type = "bold_italic";
      }
      else {
        $type = "bold";
      }
    }
    elseif (preg_match("/italic|oblique/i", $type)) {
      $type = "italic";
    }
    else {
      $type = "normal";
    }
      
    return $type;
  }
  
  static function install_fonts($files) {
    $names = array();
    
    foreach($files as $file) {
      $font = Font::load($file);
      $records = $font->getData("name", "records");
      $type = self::get_type($records[2]);
      $names[mb_strtolower($records[1])][$type] = $file;
    }
    
    return $names;
  }
  
  static function get_system_fonts() {
    $files = glob("/usr/share/fonts/truetype/*.ttf") +
             glob("/usr/share/fonts/truetype/*/*.ttf") +
             glob("/usr/share/fonts/truetype/*/*/*.ttf") +
             glob("C:\\Windows\\fonts\\*.ttf") + 
             glob("C:\\WinNT\\fonts\\*.ttf") + 
             glob("/mnt/c_drive/WINDOWS/Fonts/");
    
    return self::install_fonts($files);
  }

  
  static function get_font_families() {
    return self::$_font_lookup;
  }

  static function set_font_family($fontname, $entry) {
    self::$_font_lookup[mb_strtolower($fontname)] = $entry;
  }
  
  static function register_font($style, $remote_file) {
    $fontname = mb_strtolower($style["family"]);
    $families = Font_Metrics::get_font_families();
    
    $entry = array();
    if ( isset($families[$fontname]) ) {
      $entry = $families[$fontname];
    }
    
    $remote_file = $remote_file;
    $local_file = DOMPDF_FONT_DIR . md5($remote_file);
    $cache_entry = $local_file;
    $local_file .= ".ttf";
    
    $style_string = Font_Metrics::get_type("{$style['weight']} {$style['style']}");
    
    if ( !isset($entry[$style_string]) ) {
      $entry[$style_string] = $cache_entry;
      
      Font_Metrics::set_font_family($fontname, $entry);
      
            if ( !is_file($local_file) ) {
        file_put_contents($local_file, file_get_contents($remote_file));
      }
      
      $font = Font::load($local_file);
      
      if (!$font) {
        return false;
      }
      
      $font->parse();
      $font->saveAdobeFontMetrics("$cache_entry.ufm");
      
            Font_Metrics::save_font_families();
    }
    
    return true;
  }
}

Font_Metrics::load_font_families();
