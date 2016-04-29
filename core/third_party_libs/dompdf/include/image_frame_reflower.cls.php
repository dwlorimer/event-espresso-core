<?php



class Image_Frame_Reflower extends Frame_Reflower {

  function __construct(Image_Frame_Decorator $frame) {
    parent::__construct($frame);
  }

  function reflow(Frame_Decorator $block = null) {
    $this->_frame->position();
    
                                $this->get_min_max_width();
    
    if ( $block ) {
      $block->add_frame_to_line($this->_frame);
    }
  }

  function get_min_max_width() {
    if (DEBUGPNG) {
            list($img_width, $img_height) = dompdf_getimagesize($this->_frame->get_image_url());
      print "get_min_max_width() ".
        $this->_frame->get_style()->width.' '.
        $this->_frame->get_style()->height.';'.
        $this->_frame->get_parent()->get_style()->width." ".
        $this->_frame->get_parent()->get_style()->height.";".
        $this->_frame->get_parent()->get_parent()->get_style()->width.' '.
        $this->_frame->get_parent()->get_parent()->get_style()->height.';'.
        $img_width. ' '.
        $img_height.'|' ;
    }

    $style = $this->_frame->get_style();

                    
    $width = ($style->width > 0 ? $style->width : 0);
    if ( is_percent($width) ) {
      $t = 0.0;
      for ($f = $this->_frame->get_parent(); $f; $f = $f->get_parent()) {
        $f_style = $f->get_style();
        $t = $f_style->length_in_pt($f_style->width);
        if ($t != 0) {
          break;
        }
      }
      $width = ((float)rtrim($width,"%") * $t)/100;     } elseif ( !mb_strpos($width, 'pt') ) {
                              $width = $style->length_in_pt($width);
    }

    $height = ($style->height > 0 ? $style->height : 0);
    if ( is_percent($height) ) {
      $t = 0.0;
      for ($f = $this->_frame->get_parent(); $f; $f = $f->get_parent()) {
        $f_style = $f->get_style();
        $t = $f_style->length_in_pt($f_style->height);
        if ($t != 0) {
          break;
        }
      }
      $height = ((float)rtrim($height,"%") * $t)/100;     } elseif ( !mb_strpos($height, 'pt') ) {
                              $height = $style->length_in_pt($height);
    }

    if ($width == 0 || $height == 0) {
            list($img_width, $img_height) = dompdf_getimagesize($this->_frame->get_image_url());
      
                        if ($width == 0 && $height == 0) {
        $width = (float)($img_width * 72) / DOMPDF_DPI;
        $height = (float)($img_height * 72) / DOMPDF_DPI;
      } elseif ($height == 0 && $width != 0) {
        $height = ($width / $img_width) * $img_height;       } elseif ($width == 0 && $height != 0) {
        $width = ($height / $img_height) * $img_width;       }
    }

    if (DEBUGPNG) print $width.' '.$height.';';

    $style->width = $width . "pt";
    $style->height = $height . "pt";

    return array( $width, $width, "min" => $width, "max" => $width);
    
  }
}
