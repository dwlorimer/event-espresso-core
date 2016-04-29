<?php



class Table_Row_Frame_Decorator extends Frame_Decorator {

    
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
  }
  
  
  
  function normalise() {

        $p = Table_Frame_Decorator::find_parent_table($this);
    
    $erroneous_frames = array();
    foreach ($this->get_children() as $child) {      
      $display = $child->get_style()->display;

      if ( $display !== "table-cell" )
        $erroneous_frames[] = $child;
    }
    
        foreach ($erroneous_frames as $frame) 
      $p->move_after($frame);
  }
  
  
}
