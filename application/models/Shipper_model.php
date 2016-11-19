<?php

class shipper_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


    function getShipperInfoById($ShipperId)
    {
    	$query = $this->db->query("select * from shippers where ShipperId='$ShipperId'");
    	$d = $query->row();
    	return $d;
    }



    function getOrderShippingInfo($OrderId){
        $query = $this->db->query("select * from order_shipping_billing_address where OrderId='$OrderId' and AddressType='shipping'");
        $d = $query->row();
        return $d;
    }





}
    ?>