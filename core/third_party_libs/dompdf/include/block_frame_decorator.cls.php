<?php



class Block_Frame_Decorator extends Frame_Decorator {
  protected $_cl;      
  
  protected $_line_boxes;

  
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    
    $this->_line_boxes = array(new Line_Box($this));
    $this->_cl = 0;
  }

  
  function reset() {
    parent::reset();
    
    $this->_line_boxes = array(new Line_Box($this));
    $this->_cl = 0;
  }

  
  
  
  function get_current_line_box() {
    return $this->_line_boxes[$this->_cl];
  }

  
  function get_current_line_number() {
    return $this->_cl;
  }

  
  function get_line_boxes() { 
    return $this->_line_boxes; 
  }

  
    function set_current_line($y = null, $w = null, $h = null, $tallest_frame = null, $left = null, $right = null) {
    $this->set_line($this->_cl, $y, $w, $h, $tallest_frame, $left, $right);
  }

  function clear_line($i) {
    if ( isset($this->_line_boxes[$i]) )
      unset($this->_line_boxes[$i]);
  }

  
  function set_line($lineno, $y = null, $w = null, $h = null, $tallest_frame = null, $left = null, $right = null) {

    if ( is_array($y) )
      extract($y);

    if (is_numeric($y))
      $this->_line_boxes[$lineno]->y = $y;

    if (is_numeric($w))
      $this->_line_boxes[$lineno]->w = $w;

    if (is_numeric($h))
      $this->_line_boxes[$lineno]->h = $h;

    if ($tallest_frame && $tallest_frame instanceof Frame)
      $this->_line_boxes[$lineno]->tallest_frame = $tallest_frame;

    if (is_numeric($left))
      $this->_line_boxes[$lineno]->left = $left;

    if (is_numeric($right))
      $this->_line_boxes[$lineno]->right = $right;
  }


  function add_frame_to_line(Frame $frame) {
    if ( !$frame->is_in_flow() ) {
      return;
    }
    
    $style = $frame->get_style();
    
    $frame->set_containing_line($this->_line_boxes[$this->_cl]);
    
    

        if ( $frame instanceof Inline_Frame_Decorator ) {

            if ( $frame->get_node()->nodeName === "br" ) {
        $this->maximize_line_height( $style->length_in_pt($style->line_height), $frame );
        $this->add_line(true);
      }

      return;
    }

            if ( $this->get_current_line_box()->w == 0 &&
         $frame->is_text_node() &&
        !$frame->is_pre() ) {

      $frame->set_text( ltrim($frame->get_text()) );
      $frame->recalculate_width();
    }

    $w = $frame->get_margin_width();

    if ( $w == 0 )
      return;

        
    
    $line = $this->_line_boxes[$this->_cl];
    if ( $line->left + $line->w + $line->right + $w > $this->get_containing_block("w"))
      $this->add_line();

    $frame->position();

    $current_line = $this->_line_boxes[$this->_cl];
    $current_line->add_frame($frame);

    if ( $frame->is_text_node() )
      $current_line->wc += count(preg_split("/\s+/", trim($frame->get_text())));

    $this->increase_line_width($w);
    
    $this->maximize_line_height($frame->get_margin_height(), $frame);
  }

  function remove_frames_from_line(Frame $frame) {
        $i = $this->_cl;

    while ($i >= 0) {
      if ( ($j = in_array($frame, $this->_line_boxes[$i]->get_frames(), true)) !== false )
        break;
      $i--;
    }

    if ( $j === false )
      return;

        while ($j < count($this->_line_boxes[$i]->get_frames())) {
      $frames = $this->_line_boxes[$i]->get_frames();
      $f = $frames[$j];
      $frames[$j] = null;
      unset($frames[$j]);
      $j++;
      $this->_line_boxes[$i]->w -= $f->get_margin_width();
    }

        $h = 0;
    foreach ($this->_line_boxes[$i]->get_frames() as $f)
      $h = max( $h, $f->get_margin_height() );

    $this->_line_boxes[$i]->h = $h;

        while ($this->_cl > $i) {
      $this->_line_boxes[ $this->_cl ] = null;
      unset($this->_line_boxes[ $this->_cl ]);
      $this->_cl--;
    }
  }

  function increase_line_width($w) {
    $this->_line_boxes[ $this->_cl ]->w += $w;
  }

  function maximize_line_height($val, Frame $frame) {
    if ( $val > $this->_line_boxes[ $this->_cl ]->h ) {
      $this->_line_boxes[ $this->_cl ]->tallest_frame = $frame;
      $this->_line_boxes[ $this->_cl ]->h = $val;
    }
  }

  function add_line($br = false) {


    $this->_line_boxes[$this->_cl]->br = $br;
    $y = $this->_line_boxes[$this->_cl]->y + $this->_line_boxes[$this->_cl]->h;

    $new_line = new Line_Box($this, $y);
    
    $this->_line_boxes[ ++$this->_cl ] = $new_line;
  }

  }
