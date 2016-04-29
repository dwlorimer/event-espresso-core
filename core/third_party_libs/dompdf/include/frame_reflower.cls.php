<?php



abstract class Frame_Reflower {

  
  protected $_frame;

  
  protected $_min_max_cache;
  
  function __construct(Frame $frame) {
    $this->_frame = $frame;
    $this->_min_max_cache = null;
  }

  function dispose() {
    clear_object($this);
  }

  
  protected function _collapse_margins() {
    $frame = $this->_frame;
    $cb = $frame->get_containing_block();
    $style = $frame->get_style();
    
    if ( !$frame->is_in_flow() ) {
      return;
    }

    $t = $style->length_in_pt($style->margin_top, $cb["h"]);
    $b = $style->length_in_pt($style->margin_bottom, $cb["h"]);

        if ( $t === "auto" ) {
      $style->margin_top = "0pt";
      $t = 0;
    }

    if ( $b === "auto" ) {
      $style->margin_bottom = "0pt";
      $b = 0;
    }

        $n = $frame->get_next_sibling();
    if ( $n && !$n->is_block() ) {
      while ( $n = $n->get_next_sibling() ) {
        if ( $n->is_block() ) {
          break;
        }
        
        if ( !$n->get_first_child() ) {
          $n = null;
          break;
        }
      }
    }
    
    if ( $n ) {
      $n_style = $n->get_style();
      $b = max($b, $n_style->length_in_pt($n_style->margin_top, $cb["h"]));
      $n_style->margin_top = "0pt";
      $style->margin_bottom = $b."pt";
    }

        
  }

  
  abstract function reflow(Frame_Decorator $block = null);

  
          function get_min_max_width() {
    if ( !is_null($this->_min_max_cache) ) {
      return $this->_min_max_cache;
    }
    
    $style = $this->_frame->get_style();

        $dims = array($style->padding_left,
                  $style->padding_right,
                  $style->border_left_width,
                  $style->border_right_width,
                  $style->margin_left,
                  $style->margin_right);

    $cb_w = $this->_frame->get_containing_block("w");
    $delta = $style->length_in_pt($dims, $cb_w);

        if ( !$this->_frame->get_first_child() )
      return $this->_min_max_cache = array($delta, $delta,"min" => $delta, "max" => $delta);

    $low = array();
    $high = array();

    for ( $iter = $this->_frame->get_children()->getIterator();
          $iter->valid();
          $iter->next() ) {

      $inline_min = 0;
      $inline_max = 0;

            while ( $iter->valid() && in_array( $iter->current()->get_style()->display, Style::$INLINE_TYPES ) ) {

        $child = $iter->current();

        $minmax = $child->get_min_max_width();

        if ( in_array( $iter->current()->get_style()->white_space, array("pre", "nowrap") ) )
          $inline_min += $minmax["min"];
        else
          $low[] = $minmax["min"];

        $inline_max += $minmax["max"];
        $iter->next();

      }

      if ( $inline_max > 0 )
        $high[] = $inline_max;

      if ( $inline_min > 0 )
        $low[] = $inline_min;

      if ( $iter->valid() ) {
        list($low[], $high[]) = $iter->current()->get_min_max_width();
        continue;
      }

    }
    $min = count($low) ? max($low) : 0;
    $max = count($high) ? max($high) : 0;

            $width = $style->width;
    if ( $width !== "auto" && !is_percent($width) ) {
      $width = $style->length_in_pt($width, $cb_w);
      if ( $min < $width )
        $min = $width;
      if ( $max < $width )
        $max = $width;
    }

    $min += $delta;
    $max += $delta;
    return $this->_min_max_cache = array($min, $max, "min"=>$min, "max"=>$max);
  }

  
  protected function _parse_string($string, $single_trim = false) {
    if ($single_trim) {
      $string = preg_replace("/^[\"\']/", "", $string);
      $string = preg_replace("/[\"\']$/", "", $string);
    }
    else {
      $string = trim($string, "'\"");
    }
    
    $string = str_replace(array("\\\n",'\\"',"\\'"),
                          array("",'"',"'"), $string);

        $string = preg_replace_callback("/\\\\([0-9a-fA-F]{0,6})(\s)?(?(2)|(?=[^0-9a-fA-F]))/",
                                    create_function('$matches',
                                                    'return chr(hexdec($matches[1]));'),
                                    $string);
    return $string;
  }
  
  
  protected function _parse_quotes() {
    
        $re = "/(\'[^\']*\')|(\"[^\"]*\")/";
    
    $quotes = $this->_frame->get_style()->quotes;
      
        if (!preg_match_all($re, "$quotes", $matches, PREG_SET_ORDER))
      return;
      
    $quotes_array = array();
    foreach($matches as &$_quote){
      $quotes_array[] = $this->_parse_string($_quote[0], true);
    }
    
    if ( empty($quotes_array) ) {
      $quotes_array = array('"', '"');
    }
    
    return array_chunk($quotes_array, 2);
  }

  
  protected function _parse_content() {

        $re = "/\n".
      "\s(counters?\\([^)]*\\))|\n".
      "\A(counters?\\([^)]*\\))|\n".
      "\s([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\3|\n".
      "\A([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\5|\n" .
      "\s([^\s\"']+)|\n" .
      "\A([^\s\"']+)\n".
      "/xi";
    
    $content = $this->_frame->get_style()->content;

    $quotes = $this->_parse_quotes();
    
        if (!preg_match_all($re, $content, $matches, PREG_SET_ORDER))
      return;
      
    $text = "";

    foreach ($matches as $match) {
      
      if ( isset($match[2]) && $match[2] !== "" )
        $match[1] = $match[2];

      if ( isset($match[6]) && $match[6] !== "" )
        $match[4] = $match[6];

      if ( isset($match[8]) && $match[8] !== "" )
        $match[7] = $match[8];

      if ( isset($match[1]) && $match[1] !== "" ) {
        
                $match[1] = mb_strtolower(trim($match[1]));

                
        $i = mb_strpos($match[1], ")");
        if ( $i === false )
          continue;

        $args = explode(",", mb_substr($match[1], 8, $i - 8));
        $counter_id = $args[0];

        if ( $match[1][7] === "(" ) {
          
          if ( isset($args[1]) )
            $type = trim($args[1]);
          else
            $type = null;

          $p = $this->_frame->lookup_counter_frame($counter_id);
          
          $text .= $p->counter_value($counter_id, $type);

        } else if ( $match[1][7] === "s" ) {
                    if ( isset($args[1]) )
            $string = $this->_parse_string(trim($args[1]));
          else
            $string = "";

          if ( isset($args[2]) )
            $type = $args[2];
          else
            $type = null;

          $p = $this->_frame->lookup_counter_frame($counter_id);
          $tmp = "";
          while ($p) {
            $tmp = $p->counter_value($counter_id, $type) . $string . $tmp;
            $p = $p->lookup_counter_frame($counter_id);
          }
          $text .= $tmp;

        } else
                    continue;

      } else if ( isset($match[4]) && $match[4] !== "" ) {
                $text .= $this->_parse_string($match[4]);

      } else if ( isset($match[7]) && $match[7] !== "" ) {
        
        if ( $match[7] === "open-quote" ) {
                    $text .= $quotes[0][0];
        } else if ( $match[7] === "close-quote" ) {
                    $text .= $quotes[0][1];
        } else if ( $match[7] === "no-open-quote" ) {
                  } else if ( $match[7] === "no-close-quote" ) {
                  } else if ( mb_strpos($match[7],"attr(") === 0 ) {

          $i = mb_strpos($match[7],")");
          if ( $i === false )
            continue;

          $attr = mb_substr($match[7], 5, $i - 5);
          if ( $attr == "" )
            continue;
            
          $text .= $this->_frame->get_parent()->get_node()->getAttribute($attr);
        } else
          continue;
      }
    }

    return $text;
  }
  
  
  protected function _set_content(){
    $frame = $this->_frame;
    $style = $frame->get_style();
  
    if ( $style->content && !$frame->get_first_child() && $frame->get_node()->nodeName === "dompdf_generated" ) {
      $content = $this->_parse_content();
      $node = $frame->get_node()->ownerDocument->createTextNode($content);
      
      $new_style = $style->get_stylesheet()->create_style();
      $new_style->inherit($style);
      
      $new_frame = new Frame($node);
      $new_frame->set_style($new_style);
      
      Frame_Factory::decorate_frame($new_frame, $frame->get_dompdf());
      $new_frame->get_decorator()->set_root($frame->get_root());
      $frame->append_child($new_frame);
    }
    
    if ( $style->counter_reset && ($reset = $style->counter_reset) !== "none" )
      $frame->reset_counter($reset);
    
    if ( $style->counter_increment && ($increment = $style->counter_increment) !== "none" )
      $frame->increment_counters($increment);
  }
}
