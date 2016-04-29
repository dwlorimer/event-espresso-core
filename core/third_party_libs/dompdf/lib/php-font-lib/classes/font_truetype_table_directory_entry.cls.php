<?php


require_once dirname(__FILE__)."/font_table_directory_entry.cls.php";


class Font_TrueType_Table_Directory_Entry extends Font_Table_Directory_Entry {
  function __construct(Font_TrueType $font) {
    parent::__construct($font);
    $this->checksum = $this->readUInt32();
    $this->offset = $this->readUInt32();
    $this->length = $this->readUInt32();
    $this->entryLength += 12;
  }
}

