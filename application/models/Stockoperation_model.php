<?php

class stockoperation_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->operation = $this->load->database('operation', TRUE);
    }

    //*here we hardcode 1 for dubai only for now *//
    function getStockCountInfoBySkuList($ItemSku)
    {
    	$query = $this->operation->query("SELECT std_location, sum(std_quantity) as quantity FROM o_stock_details WHERE (std_sku='$ItemSku')  GROUP BY std_location HAVING (SUM(std_quantity) > 0) ");
    	$d = $query->result();
    	$array=array();
    	if($query->num_rows()>0){
	    	foreach ($d as $key => $value) {
	    		$array[$value->std_location]=$value->std_location.'&nbsp;&nbsp;'.$value->quantity;
	    	}
    	}
    	else{
    	}
    	return $array;
    }








    function stockOutByOrder(
            $orderId
            ,$orderNumber
            ,$orderItemId
            ,$stockIdString
            ,$itemSku
            ,$itemId
            ,$stockOutQty
        )
    {
        /*
          STEP1 UPDATE ORDER ITEM STATUS
        */


              ///SESSION INFORMATION
              $session                =   $this->session->userdata('logged_in');
              $added_by               =   $session['user_full_name'];
              $added_by_id            =   $session['user_id'];


            $update_item_query = "UPDATE 
                    order_items
                    SET 
                    RemainingItem=OrderdQty-(ItemStatusQty+$stockOutQty),
                    StockOutQty=StockOutQty+$stockOutQty,
                    ItemStatusQty=ItemStatusQty+$stockOutQty,
                    ItemStatus='stock-out',
                    ModifiedBy='$added_by'
                    where OrderItemId=$orderItemId
                    ";

            $this->db->query($update_item_query);



        /*
            STEP2 UPDATING STOCK
        */



            $stockId = '';
            $stockLocationTitle = $stockIdString;

            $inserted_array = array(
                                'stockLocationTitle' => $stockLocationTitle,
                                'StockLocationId'    => $stockId,
                                'OperationType'      => 'out',
                                'ItemId'             => $orderItemId,
                                'ItemSku'            => $itemSku,
                                'Qty'                => '-'.$stockOutQty,
                                'OrderNumber'        => $orderNumber,
                                'OrderId'            => $orderId,
                                'UserName'           =>  $added_by
                                );
        $this->db->insert('stock_info',$inserted_array);

       


        

    }




function post_to_operation(
            $orderId
            ,$orderNumber
            ,$orderItemId
            ,$stockIdString
            ,$itemSku
            ,$itemId
            ,$stockOutQty
        )
{

    $option=array();

    $option['stock_out']='true';
    $option['oorder_no']=$orderNumber;
    $option['oo_sku_so']=$itemSku;
    $option['oo_sku_qty']=$stockOutQty;
    $option['ns_std_location']=$stockIdString;

    http_build_query_for_curl( $option, $post );


        $ch = curl_init("http://operation.kingsouq.com/auto_stock_out_opr.php");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             
        $response = curl_exec($ch);

//echo "<pre>";
                echo '<div class="box-body">
                          <div class="alert alert-danger alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4>    <i class="icon fa fa-check"></i> Message !</h4>'.$response.' 
                          </div>
                    </div>';
//echo "</pre>";  

}



}
    ?>