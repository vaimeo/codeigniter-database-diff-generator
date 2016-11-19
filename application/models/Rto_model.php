<?php
Class Rto_model extends CI_Model
{
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


    function countRtoByAwb($AwbNumber){
         $query = $this->db->query("select * from rto_master where AwbNumber='$AwbNumber'");
        return $query->num_rows();
      
    }





    function addSubmit($request_key)
    {


              ///SESSION INFORMATION
          $session                =   $this->session->userdata('logged_in');
          $added_by               =   $session['user_full_name'];
          $added_by_id            =   $session['user_id'];




		$delivery_id = $this->input->post('delivery_id');
		$rto_remarks = $this->input->post('rto_remarks');
		$post_delivery_item_id_array = $this->input->post('delivery_item_id');
		
		if($delivery_id)
		{
			

			///FIRST GET DELIVERY ITEMS SO CROSS CHECK AND UPDATE TO RTO ITEMS
			$delivery_object = $this->delivery_model->getDeliveryInfoById($delivery_id);
			$delivery_items  = $this->delivery_model->getDeliveryItemsByDeliveryId($delivery_id);


			//print_r($items);
			$TotalReturnItems = count($post_delivery_item_id_array);


				$data = array(
					'OrderReferenceNumber'  => $delivery_object->OrderReferenceNumber,
					'OrderNumber'       	=> $delivery_object->OrderNumber,
					'ReasonDetails'     	=> $rto_remarks,
					'AwbNumber' 			=> $delivery_object->AwbNumber,
					'Amount' 				=> $delivery_object->DeliveryAmount,
					'Currency' 				=> $delivery_object->Currency,
					'CourierId' 			=> $delivery_object->CourierId,
					'CourierName' 			=> $delivery_object->CourierName,
					'TotalReturnItems'  	=> $TotalReturnItems,
					'ForwardDateTime'		=> $delivery_object->InsertedTime,
	                'InsertedBy'            => $added_by,
	                'InsertedById'          => $added_by_id,
	                'RequestKey'            => $request_key,
				);
				$this->db->insert('rto_master',$data);
				$RtoId = $this->db->insert_id();






    foreach ($delivery_items as $key => $delivery_item)
        {
        	$delivery_item=(object)$delivery_item;

        	if(in_array($delivery_item->DeliveryItemId,$post_delivery_item_id_array))
        	{
        		 $delivery_item_array[] = array(
                                'sku'=>$delivery_item->ItemSku,
                                'order_qty'=>str_replace('-','',$delivery_item->QtySent),
                                'name'=>$delivery_item->ItemTitle
                            );

                    $ItemElements[] = array(
                        'RtoId'   =>    $RtoId,
                        'ItemSku'     =>    $delivery_item->ItemSku,
                        'ItemTitle'   =>    $delivery_item->ItemTitle,
                        'QtySent'   =>    str_replace('-','',$delivery_item->QtySent),
                        'OrderNumber'=> $delivery_item->OrderNumber,
                        'OrderReferenceNumber'=> $delivery_item->OrderReferenceNumber,
                        'AwbNumber'=>$delivery_item->AwbNumber,
                        'OrderId'=>$delivery_item->OrderId
                        );            
        	}

                 

        }



       $ItemResult=$this->db->insert_batch('rto_items', $ItemElements);
     


        $this->db->where('RtoId',$RtoId);
        $this->db->update('rto_master',array('ItemsInfo'=>json_encode($delivery_item_array)));
		}
	
    }

}