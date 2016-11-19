<?php
Class purchase_model extends CI_Model
{

 function getOrderNumberSequence()
 {
    $date= date('Y-m-d');

    $query = $this->db->query("select * from purchase_order where CreatedDate like '%$date%'");
    $d = $query->result();
    $array=array();
    
    $total_orders = $query->num_rows();
    $new_order_total = ($total_orders)+1;
    $new_order_total_len = strlen($new_order_total);

    if($new_order_total_len==1){
      $new_order_total = '00'.$new_order_total;
    }elseif($new_order_total_len==2){
      $new_order_total = '0'.$new_order_total;
    }elseif($new_order_total_len==3){
      $new_order_total = $new_order_total;
    }


    return  'PO-'.date('dmY').'-'.$new_order_total;


 }


 function approveOrder($PurchaseOrderId)
 {
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

      $update_array = array(
        'Status'=>'approved',
        'StatusUpdatedTime' => date('Y-m-d h:m:s'),
        'StatusUpdatedBy'   =>  $added_by,
        'StatusUpdatedById'   =>  $added_by_id
        );

      $this->db->where('PurchaseOrderId',$PurchaseOrderId);
      $this->db->update('purchase_order',$update_array);

 }


 function holdOrder($PurchaseOrderId)
 {
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

      $update_array = array(
        'Status'=>'hold',
        'StatusUpdatedTime' => date('Y-m-d h:m:s'),
        'StatusUpdatedBy'   =>  $added_by,
        'StatusUpdatedById'   =>  $added_by_id
        );

      $this->db->where('PurchaseOrderId',$PurchaseOrderId);
      $this->db->update('purchase_order',$update_array);

 }



 function cancelOrder($PurchaseOrderId)
 {
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

      $update_array = array(
        'Status'=>'cancelled',
        'StatusUpdatedTime' => date('Y-m-d h:m:s'),
        'StatusUpdatedBy'   =>  $added_by,
        'StatusUpdatedById'   =>  $added_by_id
        );

      $this->db->where('PurchaseOrderId',$PurchaseOrderId);
      $this->db->update('purchase_order',$update_array);

 }



 function rejectOrder($PurchaseOrderId)
 {
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

      $update_array = array(
        'Status'=>'rejected',
        'StatusUpdatedTime' => date('Y-m-d h:m:s'),
        'StatusUpdatedBy'   =>  $added_by,
        'StatusUpdatedById'   =>  $added_by_id
        );
      $this->db->where('PurchaseOrderId',$PurchaseOrderId);
      $this->db->update('purchase_order',$update_array);

 }



 function recheckOrder($PurchaseOrderId)
 {
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

      $update_array = array(
        'Status'=>'recheck',
        'StatusUpdatedTime' => date('Y-m-d h:m:s'),
        'StatusUpdatedBy'   =>  $added_by,
        'StatusUpdatedById'   =>  $added_by_id
        );
      $this->db->where('PurchaseOrderId',$PurchaseOrderId);
      $this->db->update('purchase_order',$update_array);


      $purchase_item_id  =  $this->session->userdata('purchase_item_id');

      if(count($purchase_item_id)>0)
      {
        foreach ($purchase_item_id as $key => $value) {
          $update_array[]=array(
                    'PurchaseOrderItemId' => $value,
                    'IsRecheck'=>'1'
                    );
        }
        $this->db->update_batch('purchase_order_item',$update_array,'PurchaseOrderItemId');
      }



 }


 
 function approvalRequestOrder($PurchaseOrderId)
 {
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];

      $update_array = array(
        'Status'=>'pending_approval',
        'StatusUpdatedTime' => date('Y-m-d h:m:s'),
        'StatusUpdatedBy'   =>  $added_by,
        'StatusUpdatedById'   =>  $added_by_id
        );
      $this->db->where('PurchaseOrderId',$PurchaseOrderId);
      $this->db->update('purchase_order',$update_array);

 }






 function addSubmit()
 {
      ///MASTER INFORMATION
      $vendor_code             =  $this->input->post('vendor_code');
      $vendor_name             =  $this->input->post('vendor_name');
      $c                       =  $this->input->post('currency');
      $c                       =  explode('-',$c);
      $currency                =  $c[0];
      $rate                    =  $c[1];
      $purchase_date           =  format_date($this->input->post('purchase_date'));
      $po_number               =  $this->getOrderNumberSequence();
      $vendor_shipping_address =  $this->input->post('shipping_address');

      ///ITEM INFORMATION      
      $sku                     =  $this->input->post('sku[]');
      $description             =  $this->input->post('description[]');
      $cost                    =  $this->input->post('cost[]');
      $current_qty             =  $this->input->post('current_qty[]');
      $order_qty               =  $this->input->post('order_qty[]');
      $amount                  =  $this->input->post('amount[]');
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];
      $station_id            =   $session['station_id'];


      ///RUNNING LOOP FOR AMOUNT CALCULATIONS

      $item_array=array();
      $GrandTotal = 0;
        foreach ($sku as $key => $value) {

            $TotalAmount = $order_qty[$key] * $cost[$key];

            $item_array[] = array(
                  'ItemSku'          =>     $value, 
                  'ItemUrl'          =>     '',
                  'ItemImageUrl'     =>     '',
                  'PartNumber'       =>     '',
                  'ItemDescription'  =>     $description[$key],
                  'CurrentQty'       =>     $current_qty[$key],
                  'OrderQty'         =>     $order_qty[$key],
                  'ItemCost'         =>     $cost[$key],
                  'TotalAmount'      =>     $TotalAmount
                );

            $GrandTotal+=$TotalAmount;
        }


      //INSERT MASTER DATA 
      $po_insert_array = array(
          'VendorName'       =>   $vendor_name,
          'VendorCode'       =>   $vendor_code, 
          'Currency'         =>   $currency, 
          'CreatedDate'      =>   $purchase_date,
          'OrderNumber'      =>   $po_number,
          'OrderTitle'       =>   'Purchase Order',
          'ShippingAddress'  =>   $vendor_shipping_address,
          'CreatedById'      =>   $added_by_id,
          'CreatedBy'        =>   $added_by,
          'OrderTotal'       =>   $GrandTotal,
          'ExchangeRate'     =>   $rate,
          'StationId'        =>   $station_id
          );
      
      $this->db->insert('purchase_order',$po_insert_array);
      


      $insert_id = $this->db->insert_id();

      foreach($item_array as $key=>$value) {
        $item_array[$key]['PurchaseOrderId'] = $insert_id;
      }

      //echo "<pre>";
      //print_r($item_array);

      $this->db->insert_batch('purchase_order_item', $item_array); 



      return array($po_number,$insert_id);
 }








 function editSubmit($OrderId)
 {
      ///MASTER INFORMATION
       ///ITEM INFORMATION      
      $sku                     =  $this->input->post('sku[]');
      $description             =  $this->input->post('description[]');
      $cost                    =  $this->input->post('cost[]');
      $current_qty             =  $this->input->post('current_qty[]');
      $order_qty               =  $this->input->post('order_qty[]');
      $amount                  =  $this->input->post('amount[]');
      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];
      $station_id             =   $session['station_id'];


      ///RUNNING LOOP FOR AMOUNT CALCULATIONS

      $item_array=array();
      $GrandTotal = 0;

      ///removing previous record 
        $this->db->where('PurchaseOrderId',$OrderId);
         $this->db->delete('purchase_order_item');

        foreach ($sku as $key => $value) {

            $TotalAmount = $order_qty[$key] * $cost[$key];

            $item_array[] = array(
                  'ItemSku'          =>     $value, 
                  'ItemUrl'          =>     '',
                  'ItemImageUrl'     =>     '',
                  'PartNumber'       =>     '',
                  'ItemDescription'  =>     $description[$key],
                  'CurrentQty'       =>     $current_qty[$key],
                  'OrderQty'         =>     $order_qty[$key],
                  'ItemCost'         =>     $cost[$key],
                  'TotalAmount'      =>     $TotalAmount,
                  'PurchaseOrderId'  =>     $OrderId
                );

            $GrandTotal+=$TotalAmount;
        }

        //insert new record
      $this->db->insert_batch('purchase_order_item', $item_array); 

      //update grand total for order
      $this->db->where('PurchaseOrderId',$OrderId);
      $this->db->update('purchase_order',array('OrderTotal' =>  $GrandTotal));


      return array($OrderId);
 }





 function addOderByItemSubmit()
 {
      ///MASTER INFORMATION
      $vendor_code             =  $this->input->post('vendor_code');
      $vendor_name             =  $this->input->post('vendor_name');
      
      $c = $this->input->post('currency');
      $c                       =  explode('-',$c);
      $currency                =  $c[0];
      $rate                    =  $c[1];
      $purchase_date           =  date('Y-m-d');
      $po_number               =  $this->getOrderNumberSequence();
      $vendor_shipping_address =  $this->input->post('shipping_address');

      ///ITEM INFORMATION      
      $sku                     =  $this->input->post('sku[]');
      $description             =  $this->input->post('description[]');
      $cost                    =  $this->input->post('cost[]');
      $current_qty             =  $this->input->post('current_qty[]');
      $order_qty               =  $this->input->post('order_qty[]');
      $thumbnail               =  $this->input->post('thumbnail[]');

      
      ///SESSION INFORMATION
      $session                =   $this->session->userdata('logged_in');
      $added_by               =   $session['user_full_name'];
      $added_by_id            =   $session['user_id'];
      $station_id             =   $session['station_id'];


      ///RUNNING LOOP FOR AMOUNT CALCULATIONS

      $item_array=array();
      $GrandTotal = 0;
        foreach ($sku as $key => $value) {

            $TotalAmount = $order_qty[$key] * $cost[$key];

            $item_array[] = array(
                  'ItemSku'          =>     $value, 
                  'ItemUrl'          =>     '',
                  'ItemImageUrl'     =>     $thumbnail[$key],
                  'PartNumber'       =>     '',
                  'ItemDescription'  =>     $description[$key],
                  'CurrentQty'       =>     $current_qty[$key],
                  'OrderQty'         =>     $order_qty[$key],
                  'ItemCost'         =>     $cost[$key],
                  'TotalAmount'      =>     $TotalAmount
                );

            $GrandTotal+=$TotalAmount;
        }


      //INSERT MASTER DATA 
      $po_insert_array = array(
          'VendorName'       =>   $vendor_name,
          'VendorCode'       =>   $vendor_code, 
          'Currency'         =>   $currency, 
          'CreatedDate'      =>   $purchase_date,
          'OrderNumber'      =>   $po_number,
          'OrderTitle'       =>   'Purchase Order',
          'ShippingAddress'  =>   $vendor_shipping_address,
          'CreatedById'      =>   $added_by_id,
          'CreatedBy'        =>   $added_by,
          'OrderTotal'       =>   $GrandTotal,
          'ExchangeRate'     =>   $rate,
          'StationId'        =>   $station_id
          );
      
      $this->db->insert('purchase_order',$po_insert_array);
      


      $insert_id = $this->db->insert_id();

      foreach($item_array as $key=>$value) {
        $item_array[$key]['PurchaseOrderId'] = $insert_id;
      }

      //echo "<pre>";
      //print_r($item_array);

      $this->db->insert_batch('purchase_order_item', $item_array); 



      return array($po_number,$insert_id);
 }





 function getOrderInfoById($OrderId)
 {
        $this->db->where('PurchaseOrderId',$OrderId);
        $this->db->select('*');
        return $this->db->get('purchase_order')->row(); 
 }

 function getOrderItemInfoById($OrderId)
 {
        $this->db->where('PurchaseOrderId',$OrderId);
        $this->db->select('*');
        return $this->db->get('purchase_order_item')->result_array(); 
 }





   function get_prl_data1($date='')
  {
    
     $query = "
      SELECT 
        oi.OrderId,
        oi.OrderItemId,
        oi.ItemSku,
        SUM(oi.OrderdQty) TotalRequiredQty,
        oi.OrderDate
         FROM `order_items` oi 
         WHERE oi.OrderDate LIKE '%$date%'
         AND oi.OrderStatus='confirmed'
         AND oi.ShipmentStatus='ready-to-book'
         GROUP BY ItemSku
      ";
    ///FIRST OF ALL QUERY FOR ALL UNDELIVERD CONFIRMED ORDER ITEMS WE HAVE TO CALCULATE
    $q = $this->db->query($query);
    $final_array = array();
    if($q->num_rows()>0)
    {
      foreach ($q->result_array() as $key => $value) {

        $sku = $value['ItemSku'];
        $total_order_qty = $value['TotalRequiredQty'];

        $stock_check_sql="
           SELECT SUM(Qty) AS Qty,ItemSku FROM `stock_info`  
             WHERE ItemSku='$sku'   
            HAVING SUM(Qty)>0 ORDER BY SUM(Qty) DESC 
            ";
        $stock_query = $this->db->query($stock_check_sql);

        $total_stock_qty=0;
        if($stock_query->num_rows()>0)
        {
          $s = $stock_query->row();
          $total_stock_qty = $s->Qty;
        }

        if($total_order_qty>$total_stock_qty)
        {
          $final_array[]=array(
              'Sku'=>$sku,
              'Qty'=>str_replace('-','', ($total_stock_qty-$total_order_qty))
            );
        }

      }
    }
   
   return $final_array;

  }






   function get_prl_data($date='')
  {
    
      $query= "
          SELECT
              `i`.`ItemSku`       AS `ItemSku`,
              `i`.`OrderdQty` AS `OrderdQty`,
              `o`.`StationId`     AS `StationId`,
              `o`.`OrderReferenceNumber`   AS `OrderReferenceNumber`,
              `o`.`OrderNumber`   AS `OrderNumber`,
              `o`.`OrderId`       AS `OrderId`,
            o.OrderDate,
            o.OrderStatus,
            o.ShipmentStatus,
                      p.Title AS ProductName,
                      p.Thumbnail AS Thumbnail,
                      p.VendorName AS VendorName

                        FROM `order_items` `i`
                          LEFT JOIN `orders` `o`
                          ON `o`.`OrderId` = `i`.`OrderId`
                             LEFT JOIN `products` `p`
                         ON `p`.`Sku` = `i`.`ItemSku`
                          HAVING 
                              o.OrderStatus='confirmed' 
                 AND  o.OrderDate LIKE  '%$date%' 
                AND o.ShipmentStatus='ready-to-book'
         ";


        $a_array =  $b_array = array();
        $q = $this->db->query($query);
        $sku_array=array();
        if($q->num_rows()>0)
        {
          foreach ($q->result_array() as $key => $value)
          {
            $sku_array[]=$sku = $value['ItemSku'];
            
           $a_array[$sku]['total_qty'][]=$value['OrderdQty'];
           $a_array[$sku]['order_reference_number'][]=$value['OrderReferenceNumber'];
           $a_array[$sku]['order_number'][]=$value['OrderNumber'];
           $a_array[$sku]['order_id'][]=$value['OrderId'];
           $a_array[$sku]['thumbnail'][]=$value['Thumbnail'];
           $a_array[$sku]['product_name'][]=$value['ProductName'];
           $a_array[$sku]['vendor_name'][]=$value['VendorName'];
           
          }
        }

     
        $b_array=array();
        foreach ($a_array as $key => $value) {
          $b_array[$key]=array(
              'total_qty'=>array_sum($a_array[$key]['total_qty']),
              'thumbnail'=>$a_array[$key]['thumbnail'][0],
              'product_name'=>$a_array[$key]['product_name'][0],
              'vendor_name'=>$a_array[$key]['vendor_name'][0],
              'order_reference_numbers'=>implode(',',$a_array[$key]['order_reference_number'])
              );
        }






  ///GENERATING STOCK ARRAY

    $bundle_sku = '"'.implode('","',$sku_array).'"';

    $stock_query = '
      SELECT SUM(Qty) AS Qty,ItemSku FROM `stock_info`  
             WHERE ItemSku IN (
             '.$bundle_sku.'
             )
             GROUP BY ItemSku
            HAVING SUM(Qty)>0 ORDER BY SUM(Qty) DESC 
      ';

    $sq = $this->db->query($stock_query);
    $stock_array = array();
    if($sq->num_rows()>0)
    {
      foreach ($sq->result_array() as $skey => $svalue) {
        $stock_array[$svalue['ItemSku']]=$svalue['Qty'];
      }
    }


$final_array=array();

//ITRATE OVER TOTAL ITEMS ARRAY AND CHECK STOCK ARRAY WITH SKU KEY
foreach ($b_array as $bkey => $bvalue) {
  $sku = $bkey;
  $total_stock_qty = 0;
  if(array_key_exists($sku,$stock_array))
  {
    $total_stock_qty = $stock_array[$sku];
  }

   if($bvalue['total_qty']>$total_stock_qty)
        {
          $final_array[] = array(
              'Sku'=>$sku,
              'Qty'=>str_replace('-','', ($total_stock_qty-$bvalue['total_qty'])),
              'Thumbnail'=>$bvalue['thumbnail'],
              'ProductName'=>$bvalue['product_name'],
              'VendorName'=>($bvalue['vendor_name']==''?'no-vendor':$bvalue['vendor_name'])
            ); 
        }

}

/*
echo "<pre>";
print_r($final_array);
echo "</pre>";
*/

   return $final_array;        

    }

 

}


?>