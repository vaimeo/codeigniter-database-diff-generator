<?php

class cities_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


    function getCitiesByCountryCodeJson($country_code,$q)
    {
    	$query = $this->db->query("select CityName from cities where CountryCode='$country_code' and CityName like '%$q%'");
    	$d = $query->result();
    	$array=array();
    	if($query->num_rows()>0){
    		foreach ($d as $key => $value) {
    			$array[]=	$value->CityName;
    		}
			
    	}
    	header('Content-Type: application/json');
    	return json_encode($array);
    }


   function getAdvCountries()
 {
        $query = $this->db->query("select * from countries");
        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->CountryCode.'-'.$value->CountryName]=$value->CountryName;
            }
        }
        else{
            $array[0]='Countries Not Found';
        }
        return $array;
    }





}
    ?>