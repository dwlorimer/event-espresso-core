<?php



class Page_Frame_Reflower extends Frame_Reflower {

  
  private $_callbacks;
  
  
  private $_canvas;
  
  
  private $_stacking_context = array();

  function __construct(Page_Frame_Decorator $frame) { parent::__construct($frame); }

  
  function add_frame_to_stacking_context(Frame $frame, $z_index) {
    $this->_stacking_context[$z_index][] = $frame;
  }
  
  function apply_page_style(Frame $frame, $page_number){
    $style = $frame->get_style();
    $page_styles = $style->get_stylesheet()->get_page_styles();
    
        if ( count($page_styles) > 1 ) {
      $odd   = $page_number % 2 == 1;
      $first = $page_number == 1;
      
      $style = clone $page_styles["base"];
    
            if ( $odd && isset($page_styles[":right"]) ) {
        $style->merge($page_styles[":right"]);
      }
      
      if ( $odd && isset($page_styles[":odd"]) ) {
        $style->merge($page_styles[":odd"]);
      }
  
            if ( !$odd && isset($page_styles[":left"]) ) {
        $style->merge($page_styles[":left"]);
      }
  
      if ( !$odd && isset($page_styles[":even"]) ) {
        $style->merge($page_styles[":even"]);
      }
      
      if ( $first && isset($page_styles[":first"]) ) {
        $style->merge($page_styles[":first"]);
      }
      
      $frame->set_style($style);
    }
  }
  
  
  
  function reflow(Frame_Decorator $block = null) {
    $fixed_children = array();
    $prev_child = null;
    $child = $this->_frame->get_first_child();
    $current_page = 0;
    
    while ($child) {
      $this->apply_page_style($this->_frame, $current_page + 1);
      
      $style = $this->_frame->get_style();
  
            $cb = $this->_frame->get_containing_block();
      $left   = $style->length_in_pt($style->margin_left,   $cb["w"]);
      $right  = $style->length_in_pt($style->margin_right,  $cb["w"]);
      $top    = $style->length_in_pt($style->margin_top,    $cb["h"]);
      $bottom = $style->length_in_pt($style->margin_bottom, $cb["h"]);
      
      $content_x = $cb["x"] + $left;
      $content_y = $cb["y"] + $top;
      $content_width = $cb["w"] - $left - $right;
      $content_height = $cb["h"] - $top - $bottom;
      
            if ($current_page == 0) {
        $children = $child->get_children();
        foreach ($children as $onechild) {
          if ($onechild->get_style()->position === "fixed") {
            $fixed_children[] = $onechild->deep_copy();
          }
        }
        $fixed_children = array_reverse($fixed_children);
      }
      
      $child->set_containing_block($content_x, $content_y, $content_width, $content_height);
      
            $this->_check_callbacks("begin_page_reflow", $child);
    
            if ($current_page >= 1) {
        foreach ($fixed_children as $fixed_child) {
          $child->insert_child_before($fixed_child->deep_copy(), $child->get_first_child());
        }
      }
      
      $child->reflow();
      $next_child = $child->get_next_sibling();
      
            $this->_check_callbacks("begin_page_render", $child);
      
      $renderer = $this->_frame->get_renderer();

            $renderer->render($child);
      
      Renderer::$stacking_first_pass = false;
      
            ksort($this->_stacking_context);
      
      foreach( $this->_stacking_context as $_frames ) {
        foreach ( $_frames as $_frame ) {
          $renderer->render($_frame);
        }
      }
      
      Renderer::$stacking_first_pass = true;
      $this->_stacking_context = array();
      
            $this->_check_callbacks("end_page_render", $child);
      
      if ( $next_child ) {
        $this->_frame->next_page();
      }

                  if ( $prev_child ) {
        $prev_child->dispose(true);
      }
      $prev_child = $child;
      $child = $next_child;
      $current_page++;
    }

        if ( $prev_child ) {
      $prev_child->dispose(true);
    }
  }  
  
    
  
  protected function _check_callbacks($event, $frame) {
    if (!isset($this->_callbacks)) {
      $dompdf = $this->_frame->get_dompdf();
      $this->_callbacks = $dompdf->get_callbacks();
      $this->_canvas = $dompdf->get_canvas();
    }
    
    if (is_array($this->_callbacks) && isset($this->_callbacks[$event])) {
      $info = array(0 => $this->_canvas, "canvas" => $this->_canvas,
                    1 => $frame, "frame" => $frame);
      $fs = $this->_callbacks[$event];
      foreach ($fs as $f) {
        if (is_callable($f)) {
          if (is_array($f)) {
            $f[0]->$f[1]($info);
          } else {
            $f($info);
          }
        }
      }
    }
  }  
}
