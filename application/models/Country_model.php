<?php

class country_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


   function getCountries($CountryId)
 {
        $query = $this->db->query("select * from vendor_document_type_config where AllowedCountry='$CountryId'");
        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->DocumentType]=$value->DocumentType;
            }
        }
        else{
            $array[0]='Document Typeb Not Found For  Vendor Country';
        }
        return $array;
    }




}
    ?>