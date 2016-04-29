<?php



class Javascript_Embedder {
  
  
  protected $_dompdf;

  function __construct(DOMPDF $dompdf) {
    $this->_dompdf = $dompdf;
  }

  function insert($code) {
    $this->_dompdf->get_canvas()->javascript($code);
  }

  function render($frame) {
    if ( !DOMPDF_ENABLE_JAVASCRIPT )
      return;
      
    $this->insert($frame->get_node()->nodeValue);
  }
}
