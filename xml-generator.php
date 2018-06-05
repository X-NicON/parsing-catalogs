<?php
class XMLSaveFromArray {

  private $xmlDoc;
  private $filepath;

  public function __construct() {
    $this->xmlDoc = new DOMDocument("1.0", "UTF-8");
    //$this->xmlDoc->formatOutput = true;
    $this->filepath = './uploads/export.xml';

    $this->createFirstStructureXML();
  }

  private function createFirstStructureXML() {
    $del = true;

    if(file_exists($this->filepath)){
      $del = unlink($this->filepath);
    }

    if($del){
      $xmldoc = new DOMDocument("1.0", "UTF-8");
      //$xmldoc->formatOutput = true;
      $xmldoc->appendChild($xmldoc->createElement('products'));
      $xmldoc->save($this->filepath);
    }
  }

  public function appendSaveXML($arrayItems){
    $xmldoc = new DOMDocument();
    //$xmldoc->formatOutput = true;
    $xmldoc->load($this->filepath);

    $this->xmlDoc = new DOMDocument("1.0", "UTF-8");
    //$this->xmlDoc->formatOutput = true;

    $this->fragmentArrToXML($arrayItems);

    $products = $xmldoc->firstChild;
    $cnt = $this->xmlDoc->getElementsByTagName('product')->length;
    for ($i = 0; $i < $cnt; ++$i) {
      $products->appendChild($xmldoc->importNode( $this->xmlDoc->getElementsByTagName('product')->item($i), true));
    }

    $xmldoc->save($this->filepath);
  }

  public function fragmentArrToXML($data, &$xmlNodeDoc = false) {

    if($xmlNodeDoc == false) {
      $xmlNodeDoc = $this->xmlDoc;
    }

    foreach ($data as $key => $value) {

      if(isset($value[0])){
        foreach ($value as $Skey => $Svalue) {
          //product
          $node = $this->xmlDoc->createElement($key);
          $xmlNodeDoc->appendChild($node);

          if(is_array($Svalue)){
            foreach ($Svalue as $SQkey => $SQvalue) {

              //names
              $subnode = $this->xmlDoc->createElement($SQkey);
              if(is_array($SQvalue)){
                $this->fragmentArrToXML( $SQvalue, $subnode);
              }else{
                $text = $this->xmlDoc->createTextNode("$SQvalue");
                $subnode->appendChild( $text );
              }
              $node->appendChild( $subnode );
            }
          }else{
            $text = $this->xmlDoc->createTextNode("$Svalue");
            $node->appendChild( $text );
          }
        }
      } else {
        $node = $this->xmlDoc->createElement($key);
        $text = $this->xmlDoc->createTextNode("$Svalue");
        $node->appendChild( $text );
        $xmlNodeDoc->appendChild($node);
      }
    }
  }

}
?>
