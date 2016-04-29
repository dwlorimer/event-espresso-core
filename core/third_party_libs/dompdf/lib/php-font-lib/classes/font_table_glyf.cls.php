<?php



class Font_Table_glyf extends Font_Table {
  const ARG_1_AND_2_ARE_WORDS    = 1;
  const ARGS_ARE_XY_VALUES       = 2;
  const ROUND_XY_TO_GRID         = 4;
  const WE_HAVE_A_SCALE          = 8;
  const MORE_COMPONENTS          = 32;
  const WE_HAVE_AN_X_AND_Y_SCALE = 64;
  const WE_HAVE_A_TWO_BY_TWO     = 128;
  const WE_HAVE_INSTRUCTIONS     = 256;
  const USE_MY_METRICS           = 512;
  const OVERLAP_COMPOUND         = 1024;
  
  protected function getGlyphData($offset, $loca, $gid){
    $font = $this->getFont();
    
    
    
    $font->seek($offset + $loca[$gid]);
    return $font->read($loca[$gid+1] - $loca[$gid]);
  }
  
  protected function _parse(){
    $font = $this->getFont();
    $offset = $font->pos();
    
    $loca = $font->getData("loca");
    $real_loca = array_slice($loca, 0, -1);     
    $data = array();
    
    foreach($real_loca as $gid => $location) {
      $data[$gid] = $this->getGlyphData($offset, $loca, $gid);
    }
    
    $this->data = $data;
  }
  
  protected function _encode() {
    $font = $this->getFont();
    $subset = $font->getSubset();
    $compoundGlyphOffsets = $font->compound_glyph_offsets;
    $data = $this->data;
    
    $loca = array();
    
    $length = 0;
    foreach($subset as $gid) {
      $loca[] = $length;
      $raw = $data[$gid];
      $len = strlen($raw);
      
      if (isset($compoundGlyphOffsets[$gid])) {
        $offsets = $compoundGlyphOffsets[$gid];
        foreach($offsets as $offset => $newGid) {
          list($raw[$offset], $raw[$offset+1]) = pack("n", $newGid);
        }
      }
      
      $length += $font->write($raw, strlen($raw));
    }
    
    $loca[] = $length;     $font->getTableObject("loca")->data = $loca;
    
    return $length;
  }
}