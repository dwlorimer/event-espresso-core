<?php



class Text_Frame_Decorator extends Frame_Decorator {
  
    protected $_text_spacing;
  
    public static $_buggy_splittext;
  
  function __construct(Frame $frame, DOMPDF $dompdf) {
    if ( !$frame->is_text_node() )
      throw new DOMPDF_Exception("Text_Decorator can only be applied to #text nodes.");
    
    parent::__construct($frame, $dompdf);
    $this->_text_spacing = null;
  }

  
  function reset() {
    parent::reset();
    $this->_text_spacing = null;
  }
  
  
    function get_text_spacing() { return $this->_text_spacing; }
      
  function get_text() {
    

    return $this->_frame->get_node()->data;
  }

  
  
              function get_margin_height() {
                $style = $this->get_style();
    $font = $style->font_family;
    $size = $style->font_size;

    

    return ($style->line_height / $size) * Font_Metrics::get_font_height($font, $size);
    
  }

  function get_padding_box() {
    $pb = $this->_frame->get_padding_box();
    $pb[3] = $pb["h"] = $this->_frame->get_style()->height;
    return $pb;
  }
  
    function set_text_spacing($spacing) {
    $style = $this->_frame->get_style();
    
    $this->_text_spacing = $spacing;
    $char_spacing = $style->length_in_pt($style->letter_spacing);
    
        $style->width = Font_Metrics::get_text_width($this->get_text(), $style->font_family, $style->font_size, $spacing, $char_spacing);
  }

  
    function recalculate_width() {
    $style = $this->get_style();
    $text = $this->get_text();
    $size = $style->font_size;
    $font = $style->font_family;
    $word_spacing = $style->length_in_pt($style->word_spacing);
    $char_spacing = $style->length_in_pt($style->letter_spacing);

    return $style->width = Font_Metrics::get_text_width($text, $font, $size, $word_spacing, $char_spacing);
  }
  
  
    
      function split_text($offset) {
    if ( $offset == 0 )
      return;

    if ( self::$_buggy_splittext ) {
            $node = $this->_frame->get_node();
      $txt0 = $node->substringData(0, $offset);
      $txt1 = $node->substringData($offset, mb_strlen($node->textContent)-1);

      $node->replaceData(0, mb_strlen($node->textContent), $txt0);
      $split = $node->parentNode->appendChild(new DOMText($txt1));
    }
    else {
      $split = $this->_frame->get_node()->splitText($offset);
    }
    
    $deco = $this->copy($split);

    $p = $this->get_parent();
    $p->insert_child_after($deco, $this, false);

    if ( $p instanceof Inline_Frame_Decorator )
      $p->split($deco);

    return $deco;
  }

  
  function delete_text($offset, $count) {
    $this->_frame->get_node()->deleteData($offset, $count);
  }

  
  function set_text($text) {
    $this->_frame->get_node()->data = $text;
  }

}

Text_Frame_Decorator::$_buggy_splittext = PHP_VERSION_ID < 50207;
