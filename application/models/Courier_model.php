<?php

class courier_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


    function getCourierList()
    {
    	$query = $this->db->query("select * from couriers");
    	$d = $query->result();
    	$array=array();
    	if($query->num_rows()>0){
	    	foreach ($d as $key => $value) {
	    		$array[$value->CourierId]=$value->CourierTitle;
	    	}
    	}
    	else{
    		$array[0]='No Courier Present';
    	}
    	return $array;
    }

    function getCourierInfoById($CourierId)
    {
        $query = $this->db->query("select * from couriers where CourierId='$CourierId'");
        $d = $query->row();
        return $d;
    }




    function generateParzelAwb()
    {


    
    $q = "SELECT * FROM awb_sequence order by AwbId desc limit 0,1";
    $result = $this->db->query($q)->row();


    $AwbId = $result->AwbId;

    $order_id_length = strlen($AwbId);
        
        $order_sting='248';

        for ($i=$order_id_length; $i < 7 ; $i++) { 
            $order_sting.='0';
        }

      $awb   =$order_sting.=$AwbId; 

    $q = "INSERT into awb_sequence values('','$awb')";
    $this->db->query($q);

        return  $awb;
        
    }


}
    ?>