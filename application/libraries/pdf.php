<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require('fpdf/fpdf.php');
class Pdf extends FPDF
{
	// Extend FPDF using this class
	// More at fpdf.org -> Tutorials

	function __construct($orientation='P', $unit='mm', $size='A4')
	{
		 $this->ci =&get_instance();
        $this->pdf = new FPDF();
	}
}
?>