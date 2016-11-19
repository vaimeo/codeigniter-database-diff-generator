<?php

class stock_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


    function getStockOutItemsByOrderId($OrderId)
    {
        $this->db->where('OrderId',$OrderId);
        return $this->db->get('stock_info')->result(); 
    }




    //*here we hardcode 1 for dubai only for now *//
    function getStockCountInfoBySkuList($ItemSku,$key_type=2)
    {


          ///SESSION INFORMATION
          $session                =   $this->session->userdata('logged_in');
          $station_id            =   $session['station_id'];


                $q="  SELECT
                      `si`.`StockId`            AS `StockId`,
                      `si`.`StationId`          AS `StationId`,
                      `si`.`ItemSku`            AS `ItemSku`,
                      `si`.`ItemTitle`          AS `ItemTitle`,
                      `si`.`StockLocationId` AS `StockLocationId`,
                      `si`.`StockLocationTitle` AS `StockLocationTitle`,
                      SUM(`si`.`Qty`)           AS `Qty`
                    FROM `stock_info` `si`
                    where ItemSku='$ItemSku'  and StationId=$station_id 
                    GROUP BY `si`.`StockLocationTitle`,`si`.`ItemSku`,`si`.`StationId`
                    HAVING (SUM(`si`.`Qty`) > 0) limit 0,1000000000000

                    ";

    	   $query = $this->db->query($q);
    	$d = $query->result();
    	$array=array();
    	if($query->num_rows()>0){
	    	foreach ($d as $key => $value) {
	    		$array[($key_type==2?$value->StockLocationTitle:$value->StockLocationId.'-'.$value->StockLocationTitle)]=$value->StockLocationTitle.'&nbsp;&nbsp;'.$value->Qty;
	    	}
    	}
    	else{
    	}
    	return $array;
    }




    //*here we hardcode 1 for dubai only for now *//
    function getStockInfoBySku($ItemSku,$all_stations=false)
    {

        $station_cond='';

        if(!$all_stations)
        {
          ///SESSION INFORMATION
          $session                =   $this->session->userdata('logged_in');
          $station_id            =   $session['station_id'];
          $station_cond   =  "and StationId=$station_id";
        }



        $query = $this->db->query("SELECT SUM(Qty) as Qty,StockLocationTitle,StockLocationId,ItemId,ItemSku FROM `stock_info`  where ItemSku='$ItemSku'   $station_cond GROUP BY StockLocationTitle  HAVING SUM(Qty)>0 ORDER BY SUM(Qty) desc  ");
        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[]=array(
                        'StockLocationId'=>$value->StockLocationId,
                        'StockLocationTitle'=>$value->StockLocationTitle,
                        'Qty'=>$value->Qty
                        );
            }
        }
        else{
        }
        return $array;
    }




    //*here we hardcode 1 for dubai only for now *//
    function getStockReportBySku($ItemSku)
    {
        $query = $this->db->query("SELECT * FROM `stock_info`  where ItemSku='$ItemSku' ");
        return $query;
    }




function getStockReportBySkuExcelExportDeta($WhereClause='')
{
    if(true)
    {
        ///CONFIG DATA OUTPUT TO EXCEL

    $column_config = array(
        'ItemSku'   =>  array('sql'=>'true','excel' => array('label'=>'Sku','auto_fill'=>'true')),
        'OperationType'   =>  array('sql'=>'true','excel' => array('label'=>'Operation','auto_fill'=>'true')),
        'StockLocationTitle' => array('sql'=>'true','excel' => array('label'=>'Bin','auto_fill'=>'true')),
        'Qty' => array('sql'=>'true','excel' => array('label'=>'Qty','auto_fill'=>'true')),
        'InsertedBy'       => array('sql'=>'true','excel' => array('label'=>'User','auto_fill'=>'true')),
        'InsertedTime'=>array('sql'=>'true','excel' => array('label'=>'Time','auto_fill'=>'true'))
        );

        $select= $excel_head_array = array();
        foreach ($column_config as $key3 => $value3) {
            if($value3['sql']=='true'){
                $select[] = $key3;
            }
                $excel_head_array[]=$value3['excel']['label'];
        }
        
        $select_columns =  implode(',',$select);

   
        $query = $this->db->query("select $select_columns from stock_info WHERE 1=1 ".$WhereClause);
        $d = $query->result_array();
        $array=array();

        $array[] =  $excel_head_array;

        if($query->num_rows()>0){

        foreach ($d as $key => $value) {
            $e=array();
            $c=0;
                $g=0;

                foreach ($column_config as $key1 => $value1) {

                    if($value1['excel']['auto_fill']=='false'){
                        $e[$value1['excel']['label']] = (array_key_exists('fill_text',$value1['excel'])?$value1['excel']['fill_text']:'');
                    }else{
                         $e[$value1['excel']['label']] = $value[$key1];

                    }
              
                }
                //print_r($e);
                 $array[]=$e;
            }
        }
        
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
            ,$price
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
   $session                =   $this->session->userdata('logged_in');
          $station_id            =   $session['station_id'];

       
        $location_object = $this->getAndUpdateLocations($stockIdString,$station_id);

        $StockLocationId = $location_object->StockLocationId;
        $StockLocationTitle = $location_object->StockLocationTitle;

            $inserted_array = array(
                                'stockLocationTitle' => $StockLocationTitle,
                                'StockLocationId'    => $StockLocationId,
                                'OperationType'      => 'out',
                                'Price'              => $price,
                                'ItemId'             => $orderItemId,
                                'ItemSku'            => $itemSku,
                                'Qty'                => '-'.$stockOutQty,
                                'OrderNumber'        => $orderNumber,
                                'OrderId'            => $orderId,
                                'InsertedBy'         => $added_by,
                                'InsertedById'       => $added_by_id
                                );
        $this->db->insert('stock_info',$inserted_array);

        $this->update_stock_flat_table($itemSku);
        $this->update_products_bin_info($itemSku);

    }


    function stockOut($request_key='')
    {

      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $station_id             =   $session['station_id'];
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

      $stock_location_title   = $this->input->post('stock_location_title');

            if($stock_location_title=='')
            {
                    $stock_location_title            = $this->input->post('location_id');
            }

        $stock_location_title = strtoupper($stock_location_title);
        
        $item_qty               = $this->input->post('item_qty');
        $item_sku               = $this->input->post('item_sku');

        $location_object = $this->getAndUpdateLocations($stock_location_title,$station_id);

        $StockLocationId = $location_object->StockLocationId;
        $StockLocationTitle = $location_object->StockLocationTitle;


        $insert_array = array(
                    'StockLocationId' =>  $StockLocationId,
                    'StockLocationTitle' => $StockLocationTitle,
                    'StationId'=>$station_id,
                    'ItemSku'=>$item_sku,
                    'Qty'=>'-'.$item_qty,
                    'OperationType'=>'Out',
                    'InsertedBy'         => $added_by,
                    'InsertedById'       => $added_by_id,
                    'RequestKey'  => $request_key
            );

        $this->db->insert('stock_info',$insert_array);
        $this->load->model('order_model');
           $this->update_stock_flat_table($item_sku);
           $this->update_products_bin_info($item_sku);

                        //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','stock_out_submit',uri_string().'/'.$request_key);           
    }




    function stockIn($request_key='')
    {

      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $station_id             =   $session['station_id'];
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

        $stock_location_title   = $this->input->post('stock_location_title');

        if($stock_location_title=='')
        {
                $stock_location_title            = $this->input->post('location_id');
        }

        $stock_location_title = strtoupper($stock_location_title);
        
        $item_qty               = $this->input->post('item_qty');
        $item_sku               = $this->input->post('item_sku');

        $location_object = $this->getAndUpdateLocations($stock_location_title,$station_id);

        $StockLocationId = $location_object->StockLocationId;
        $StockLocationTitle = $location_object->StockLocationTitle;


        $insert_array = array(
                    'StockLocationId' =>  $StockLocationId,
                    'StockLocationTitle' => $StockLocationTitle,
                    'StationId'=>$station_id,
                    'ItemSku'=>$item_sku,
                    'Qty'=>$item_qty,
                    'OperationType'=>'In',
                    'InsertedBy'         => $added_by,
                    'InsertedById'       => $added_by_id,
                    'RequestKey'  => $request_key
            );

        $this->db->insert('stock_info',$insert_array);
        $this->load->model('order_model');
           $this->update_stock_flat_table($item_sku);
           $this->update_products_bin_info($item_sku);

                        //DELTE JOB EXECUTION FOR THIS PROCESS
        $this->general_model->removeExecutionState('add','stock_in_submit',uri_string().'/'.$request_key);           
    }






    function getAndUpdateLocations($stock_location_title,$station_id)
    {



          $query = $this->db->query("SELECT * FROM stock_locations WHERE StockLocationTitle='".$stock_location_title."' and StationId=$station_id ");
            if($query->num_rows()==1)
            {
                $l = (object)$query->row();
                $result = array(
                        'StockLocationId'=>$l->StockLocationId,
                        'StockLocationTitle'=>$l->StockLocationTitle
                        ); 
            }
            else
            {

                $insert_array = array(
                                    'StockLocationTitle'=>$stock_location_title,
                                    'StationId'=>$station_id
                            );
                $this->db->insert('stock_locations',$insert_array);
                $location_id=$this->db->insert_id();

                $result = array(
                    'StockLocationId'=>$location_id,
                    'StockLocationTitle'=>$stock_location_title
                    ); 

            }

        return (object)$result; 
    }

    function getStockInfoByOrderNumber($OrderNumber)
    {
        $OrderNumber=trim($OrderNumber);
        $query = $this->db->query("SELECT * FROM stock_info WHERE OrderNumber='".$OrderNumber."' ");
        return $query->row();
    }



    function getStockLocationTitleById($StockLocationId){
        $this->db->where('StockLocationId',$StockLocationId);
        $c = $this->db->get('stock_locations')->row()->StockLocationTitle;
        return $c; 
    }




    ////updating products bin column on based of current orders
function update_products_bin_info($item_sku)
    {

        $this->load->model('order_model');
         $item_stock_info       =  $this->stock_model->getStockInfoBySku($item_sku);
         $total_items_on_order  =  $this->order_model->countOnOrderItemBySku($item_sku);

         $bin_qty_array= array();

            $total_stock_qty=0;
            foreach ($item_stock_info as $key => $value) {
                
                if(count($value)>0)
                {
                    $bin_qty_array[$value['StockLocationTitle']] = $value['Qty'];
                    $total_stock_qty+=$value['Qty'];
                }


                $bin_qty_json=json_encode($bin_qty_array,true);


                $this->db->where('Sku',$item_sku);
                $this->db->update('products',array('Bins'=>$bin_qty_json,'StockQty'=>$total_stock_qty,'OnOrderQty'=>$total_items_on_order,'AvailableQty'=>$total_stock_qty-$total_items_on_order));
            }
}






 function update_stock_flat_table($sku='')
    {
        $this->load->model('order_model');
        $sku_cond='';
        if($sku!='')
        {
            $sku_cond=" WHERE ItemSku ='$sku'";
        }

        $e = $this->db->query("
                    SELECT
                      `si`.`StockId`            AS `StockId`,
                      `si`.`StationId`          AS `StationId`,
                      `si`.`ItemSku`            AS `ItemSku`,
                      `si`.`ItemTitle`          AS `ItemTitle`,
                      `si`.`StockLocationTitle` AS `StockLocationTitle`,
                      SUM(`si`.`Qty`)           AS `Qty`
                    FROM `stock_info` `si`
                    $sku_cond
                    GROUP BY `si`.`StockLocationTitle`,`si`.`ItemSku`,`si`.`StationId`
                    HAVING (SUM(`si`.`Qty`) > 0) limit 0,1000000000000
            ");

        $r = $e->result_array();
        $insert_array=array();
        
        foreach ($r as $key => $value) {
             $total_items_on_order  =  $this->order_model->countOnOrderItemBySku($value['ItemSku']);
            $insert_array[]=array(
                    'ItemSku'=>$value['ItemSku'],
                    'StockLocationTitle'=>$value['StockLocationTitle'],
                    'ItemTitle'=>$value['ItemTitle'],
                    'Qty'=>$value['Qty'],
                    'OnOrderQty'=>$total_items_on_order,
                    'StationId'=>$value['StationId']
                );
        }

        if($sku!='')
        {
            $this->db->where('ItemSku',$sku);
            $this->db->delete('stock_info_flat');
        }


        if(count($insert_array)>0)
        {
            $this->db->insert_batch('stock_info_flat',$insert_array);
        }




    }



}
    ?>