<?php
Class barcode_model extends CI_Model
{




public function barcode_genrate_and_save($code)
{
    $this->load->library('zend');
    $this->zend->load('Zend/Barcode');

    $barcodeOptions = array('text' => $code, 'font' => 5);
 
    $rendererOptions = array('fontSize'=>33,'imageType'=>'jpg');
    
    $file = Zend_Barcode::draw(
    'code128', 'image', $barcodeOptions, $rendererOptions
    );
   $store_image = imagepng($file,"uploads/products/barcodes/{$code}.".$rendererOptions['imageType']);
   //return $code.'.'.$rendererOptions['imageType'];
}




}
?>