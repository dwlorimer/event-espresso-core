<?php



class Table_Row_Group_Frame_Decorator extends Frame_Decorator {

  
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
  }

  
  function split($child = null, $force_pagebreak = false) {

    if ( is_null($child) ) {
      parent::split();
      return;
    }


        $cellmap = $this->get_parent()->get_cellmap();
    $iter = $child;

    while ( $iter ) {
      $cellmap->remove_row($iter);
      $iter = $iter->get_next_sibling();
    }

            if ( $child === $this->get_first_child() ) {
      $cellmap->remove_row_group($this);
      parent::split();
      return;
    }
    
    $cellmap->update_row_group($this, $child->get_prev_sibling());
    parent::split($child);
    
  }
}
 
