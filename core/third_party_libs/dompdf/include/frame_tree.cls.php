<?php



class Frame_Tree {
    
  
  static protected $_HIDDEN_TAGS = array("area", "base", "basefont", "head", "style",
                                         "meta", "title", "colgroup",
                                         "noembed", "noscript", "param", "#comment");  
  
  protected $_dom;

  
  protected $_root;

  
  protected $_absolute_frames;

  
  protected $_registry;
  

  
  function __construct(DomDocument $dom) {
    $this->_dom = $dom;
    $this->_root = null;
    $this->_registry = array();
  }
  
  function __destruct() {
    clear_object($this);
  }

  
  function get_dom() { return $this->_dom; }

  
  function get_root() { return $this->_root; }

  
  function get_frame($id) { return isset($this->_registry[$id]) ? $this->_registry[$id] : null; }

  
  function get_frames() { return new FrameTreeList($this->_root); }
      
  
  function build_tree() {
    $html = $this->_dom->getElementsByTagName("html")->item(0);
    if ( is_null($html) )
      $html = $this->_dom->firstChild;

    if ( is_null($html) )
      throw new DOMPDF_Exception("Requested HTML document contains no data.");

    $this->fix_tables();
    
    $this->_root = $this->_build_tree_r($html);

  }
  
  
  protected function fix_tables(){
    $xp = new DOMXPath($this->_dom);
    
            $captions = $xp->query("//table/caption");
    foreach($captions as $caption) {
      $table = $caption->parentNode;
      $table->parentNode->insertBefore($caption, $table);
    }
    
    $rows = $xp->query("//table/tr");
    foreach($rows as $row) {
      $tbody = $this->_dom->createElement("tbody");
      $tbody = $row->parentNode->insertBefore($tbody, $row);
      $tbody->appendChild($row);
    }
  }

  
  protected function _build_tree_r(DomNode $node) {
    
    $frame = new Frame($node);
    $id = $frame->get_id();
    $this->_registry[ $id ] = $frame;
    
    if ( !$node->hasChildNodes() )
      return $frame;

                
        $children = array();
    for ($i = 0; $i < $node->childNodes->length; $i++)
      $children[] = $node->childNodes->item($i);

    foreach ($children as $child) {
      $node_name = mb_strtolower($child->nodeName);
      
            if ( in_array($node_name, self::$_HIDDEN_TAGS) )  {
        if ( $node_name !== "head" &&
             $node_name !== "style" ) 
          $child->parentNode->removeChild($child);
        continue;
      }

            if ( $node_name === "#text" && $child->nodeValue == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }

            if ( $node_name === "img" && $child->getAttribute("src") == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }
      
      $frame->append_child($this->_build_tree_r($child), false);
    }
    
    return $frame;
  }
  
  public function insert_node(DOMNode $node, DOMNode $new_node, $pos) {
    if ($pos === "after" || !$node->firstChild)
      $node->appendChild($new_node);
    else 
      $node->insertBefore($new_node, $node->firstChild);
    
    $this->_build_tree_r($new_node);
    
    $frame_id = $new_node->getAttribute("frame_id");
    $frame = $this->get_frame($frame_id);
    
    $parent_id = $node->getAttribute("frame_id");
    $parent = $this->get_frame($parent_id);
    
    if ($pos === "before")
      $parent->prepend_child($frame, false);
    else 
      $parent->append_child($frame, false);
      
    return $frame_id;
  }
}
