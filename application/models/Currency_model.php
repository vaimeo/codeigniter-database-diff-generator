<?php

class currency_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


    function getCurrenciesList($ForSearch=false)
    {
    	$query = $this->db->query("select * from currency");
    	$d = $query->result();
      $array=array();
        if($ForSearch)
        {
               $array['ALL']='All';
        }
    	if($query->num_rows()>0){
	    	foreach ($d as $key => $value) {
	    		$array[$value->CurrencyCode]=$value->CurrencyCode;
	    	}
    	}
    	else{
    		$array[0]='No Currency Present';
    	}
    	return $array;
    }




    function getVendorAllowedCurrenciesList($ForSearch=false)
    {
        $query = $this->db->query("select * from currency where CurrencyCode in ('USD','AED','KWD','SAR','EGP','OMR','EUR','BHD','RUB','GBP','HKD','CNY')");
        $d = $query->result();
        $array=array();
        if($ForSearch)
        {
               $array['ALL']='All';
        }
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->CurrencyCode]=$value->CurrencyCode;
            }
        }
        else{
            $array[0]='No Currency Present';
        }
        return $array;
    }


    function getCurrenciesListWithRate($ForSearch=false)
    {
        $query = $this->db->query("SELECT CurrencyTo,Rate FROM `currency_rate` WHERE CurrencyFrom='USD'");
        $d = $query->result();
         $array=array();
        if($ForSearch)
        {
               $array['ALL']='All';
        }
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->CurrencyTo.'-'.$value->Rate]=$value->CurrencyTo.'-'.number_format($value->Rate, 2, '.', '');
            }
        }
        else{
            $array[0]='No Currency Present';
        }
        return $array;
    }

    function getUSDExchangeRate($FromCurrency=false)
    {
        $d = $this->db->query("SELECT Rate FROM `currency_rate` WHERE  CurrencyFrom='USD' and CurrencyTo='$FromCurrency'");
        $e=0;
        if($d->num_rows()>0){
            $d =$d->row();
           $e = number_format((float)$d->Rate, 2, '.', '');
        }
        return $e;
    }

    function getExchangeRate($FromCurrency=false,$ToCurrency=false)
    {
        $d = $this->db->query("SELECT Rate FROM `currency_rate` WHERE  CurrencyFrom='$FromCurrency' and CurrencyTo='$ToCurrency'");
         $e=0;
        if($d->num_rows()>0){
            $d =$d->row();
           $e = number_format((float)$d->Rate, 2, '.', '');
        }
        return $e;
    }



    function getCurrencyRateGroupData()
    {
            $d = $this->db->query("SELECT CurrencyCode FROM `currency`");
            return $d->result();
    }

            
                








}
    ?>