<?php

class customer_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


    function getCustomerInfoById($CustomerId)
    {
        //IF CUSTOMER ID IS NULL THEN WE WILL GET CUSTOMER ID FROM ORDER
	   $query = $this->db->query("select * from customer where CustomerId='$CustomerId'");
    	$d = $query->row();
    	return $d;
    }






}
    ?>