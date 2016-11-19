<?php

class order_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    function getItemsByOrderId($OrderId)
    {
    	$this->db->where('OrderId',$OrderId);
    	return $this->db->get('order_items')->result();	
    }



    function getItemInfoByOrderId($SkuItem,$OrderId)
    {
        $this->db->where('OrderId',$OrderId);
        $this->db->where('ItemSku',$SkuItem);
        return $this->db->get('order_items')->row(); 
    }



    function getCustomerIdByOrderId($OrderId)
    {
        $this->db->where('OrderId',$OrderId);
        $this->select('CustomerId');
        $c = $this->db->get('orders')->row();
        return $c->CustomerId; 
    }



    function getOrderInfoById($OrderId)
    {
        $this->db->where('OrderId',$OrderId);
        $c = $this->db->get('order_master_view')->row();
        return $c; 
    }



    function getOrderTableInfoById($OrderId)
    {
        $this->db->where('OrderId',$OrderId);
        $c = $this->db->get('orders')->row();
        return $c; 
    }




    function getOrderBillingInfo($OrderId)
    {
        $this->db->where('OrderId',$OrderId);
        $this->db->where('AddressType','billing');
        $c = $this->db->get('order_shipping_billing_address')->row();
        return $c; 
    }


    function getOrderShippingInfo($OrderId)
    {
        $this->db->where('OrderId',$OrderId);
        $this->db->where('AddressType','shipping');
        $c = $this->db->get('order_shipping_billing_address')->row();
        return $c; 
    }

    
    function getOrderInfoByOrderNumber($OrderNumber)
    {
        $this->db->where('OrderReferenceNumber',$OrderNumber);
        $c = $this->db->get('orders')->row();
        return $c; 
    }


   
    function getTotalOrdersByParentId($OrderNumber)
    {
        $this->db->where('ParentId',$OrderNumber);
        $c = $this->db->get('orders')->num_rows()+1;
        return $c; 
    }





function getOrdersJsonBySearch($q)
{
    $Cond=" Where OrderReferenceNumber Like '%$q%' or  OrderNumber Like '%$q%'";


    $query = $this->db->query("select OrderId,OrderReferenceNumber,OrderSource from orders $Cond");
    $d = $query->result();
    $array=array();
    if($query->num_rows()>0){
        foreach ($d as $key => $value) {
            $array[]=   $value;
        }
        
    }


    header('Content-Type: application/json');
    return json_encode($array);
}




    function getItemHistory($OrderId,$ItemSku){
        $this->db->where('OrderId',$OrderId);
        $this->db->where('ItemSku',$ItemSku);
        $d  =   $this->db->get('order_item_history');

        $data_array=array();
        if($d->num_rows()>0){
            $data_array = $d->result(); 
        }
        return $data_array;
    }



    function updateCustomer($customer_info_array){

        $this->db->where('Email',$customer_info_array['Email']);
        $c = $this->db->get('customer');

        $array=array();
        if($c->num_rows()==0){
            $this->db->insert('customer',array(
                    'FirstName'=> $customer_info_array['FirstName'],
                    'LastName'=>  $customer_info_array['LastName'],
                    'Email'=> $customer_info_array['Email']
                ));
            $cusotmer_id= $this->db->insert_id();

        }
        else
        {
                $r = $c->row();
                $cusotmer_id= $r->CustomerId;
        }

        return $cusotmer_id;
    }
    
    function countOnOrderItemBySku($item_sku){
        $total_qty=0;
        $d  =   $this->db->query('SELECT SUM(OrderdQty) AS  TotalOnOrderQty FROM `order_items`  WHERE ItemSku="'.$item_sku.'"   AND CountOnOrder=1');
        if($d->num_rows()>0){
            $d = $d->row(); 
            $total_qty = $d->TotalOnOrderQty;
        }
        return $total_qty;
    }





    function insertOrder($order_array)
    {

        $OrderStatus            =  strtolower($order_array['OrderStatus']);
        $OrderSource            =  $order_array['OrderSource'];   
        $OrderReferenceNumber   =  $order_array['OrderReferenceNumber']; 
        $OrderDate              =  date('Y-m-d h:i:s',strtotime($order_array['OrderDate']));
        $History                =  $order_array['History'];
        $PaymentMethod          =  strtolower($order_array['PaymentMethod']);
        $CustomerCurrency       =  $order_array['CustomerCurrency'];
        $ShippingCharges        =  $order_array['ShippingCharges'];
        $DiscountAmount         =  $order_array['DiscountAmount'];
        $CodCarges              =  $order_array['CodCarges'];
        $TotalAmount            =  $order_array['TotalAmount'];
        $AwbNumber              =  $order_array['AwbNumber'];
        $ExchangeRate           =  $order_array['ExchangeRate'];
        $ZipCode                =  $order_array['ShippingAddress']['ZipCode'];
        $ShippingCountry        =  $order_array['ShippingAddress']['Country'];

       

            //CHECKING IF CUSTOMER WITH THIS NAME EXIST OR NOT WE WILL CONSIDER CUSTOMER WITH BILLING INFO
            $customer_id        =  $this->updateCustomer($order_array['CustomerInfo']);
            $OrderSourceObject  =  $this->general_model->getOrderSourceInfo($OrderSource);


             $order_insert_array = array(
                'OrderReferenceNumber' =>  $OrderReferenceNumber,
                'PaymentMethod'        =>  $PaymentMethod,
                'OrderStatus'          =>  $OrderStatus,
                'ShipmentStatus'       =>  'ready-to-book',
                'CustomerId'           =>  $customer_id,
                'CustomerCurrency'     =>  $CustomerCurrency,
                'StoreCurrency'        =>  'USD',
                'ShippingCharges'      =>  $ShippingCharges,
                'BaseShippingCharges'  =>  $ShippingCharges/$ExchangeRate,
                'TotalAmount'          =>  $TotalAmount,
                'BaseTotalAmount'      =>  $TotalAmount/$ExchangeRate,
                'Discount'             =>  $DiscountAmount,
                'BaseDiscount'         =>  $DiscountAmount/$ExchangeRate,
                'TotalOrderItems'      =>  count($order_array['Items']),
                'OrderDate'            =>  $OrderDate ,
                'OrderSource'          =>  $OrderSource,
                'OrderSourceId'        =>  '0',
                'StationId'            =>  '1',
                'ExchangeRate'         =>  $ExchangeRate
                );


                $this->db->insert('orders',$order_insert_array);
                $order_id= $this->db->insert_id();


                foreach ($order_array['Items'] as $item_array)
                {
                    $sku            =  $item_array['Sku'];
                    $name           =  $item_array['Name'];
                    $order_qty      =  $item_array['Qty'];
                    $price          =  $item_array['Price'];
                    $base_price     =  $price/$ExchangeRate;
                    $subtotal       =  $item_array['Amount'];
                    $base_subtotal  =  $subtotal/$ExchangeRate;

                    $item_object     =  $this->product_model->getProductInfoBySku($sku);
                    $item_stock_info =  $this->stock_model->getStockInfoBySku($sku,true);

                       

                     $bin_qty_array= array();

                        $total_stock_qty=0;
                        foreach ($item_stock_info as $key => $value) {
                            
                            if(count($value)>0)
                            {
                                $bin_qty_array[$value['StockLocationTitle']] = $value['Qty'];
                                $total_stock_qty+=$value['Qty'];
                            }
                        }


                       $order_item_array[] = array(
                                    'sku'               =>  $sku,
                                    'thumbnail'         =>  $item_object->Thumbnail,
                                    'order_qty'         =>  $order_qty,
                                    'name'              =>  $name,
                                    'bin_qty'           =>  $bin_qty_array,
                                    'total_stock_qty'   =>  $total_stock_qty
                                );


                        $ItemElements[] = array(
                            'OrderId'        =>    $order_id,
                            'ItemSku'        =>    $sku,
                            'ItemTitle'      =>    $name,
                            'ItemPrice'      =>    $price,
                            'BasePrice'      =>    $base_price,
                            'Subtotal'       =>    $subtotal,
                            'BaseSubtotal'   =>    $base_subtotal,
                            'OrderdQty'      =>    $order_qty,
                            'RemainingItem'  =>    $order_qty,
                            'OrderDate'      =>    $OrderDate,
                            'Thumbnail'      =>    $item_object->Thumbnail,
                            'OrderStatus'          =>  $OrderStatus,
                            'ShipmentStatus'       =>  'ready-to-book',                            
                        );
         
                    }

                    $omigo_order_number = generate_omigo_order_number($order_id);

                    $this->db->where('OrderId',$order_id);
                    $this->db->update('orders',array('OrderNumber'=>$omigo_order_number,'ItemsInfo'=>json_encode($order_item_array)));

                    $ItemResult=$this->db->insert_batch('order_items', $ItemElements);







                //BILLING ADDRESS  HERE WE NEED TO ADD 1 MORE CONDITON IF SKU IS NOT PRESENT IN OMIGO WE NEED TO IMPORT THIS PRODUCT FIRST IN OMIGO THEN WE CAN USE THIS PRODUCT          
                $billing_array = $order_array['BillingAddress'];

                $OrderElements = array();

                $OrderElements[] = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $billing_array['FirstName'],
                    'MiddleName'    =>  '',
                    'LastName'      =>  $billing_array['LastName'],
                    'Email'         =>  $billing_array['Email'],
                    'Telephone'     =>  $billing_array['Telephone'],
                    'Fax'           =>  '',
                    'ZipCode'       =>  (array_key_exists('ZipCode',$billing_array)?$billing_array['ZipCode']:''),
                    'Company'       =>  (array_key_exists('Company',$billing_array)?$billing_array['Company']:''),
                    'Country'       =>  $billing_array['Country'],
                    'Street'        =>  $billing_array['Address'],
                    'City'          =>  $billing_array['City'],
                    'Region'        =>   (array_key_exists('Region',$billing_array)?$billing_array['Region']:''),
                    'AddressType'   =>  'billing'
                 );


                
                //SHIPPING ADDRESS               
                $shipping_array = $order_array['ShippingAddress'];

                $OrderElements[] = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $shipping_array['FirstName'],
                    'MiddleName'    =>  '',
                    'LastName'      =>  $shipping_array['LastName'],
                    'Email'         =>  $shipping_array['Email'],
                    'Telephone'     =>  $shipping_array['Telephone'],
                    'Fax'           =>  '',
                    'ZipCode'       =>  (array_key_exists('ZipCode',$shipping_array)?$shipping_array['ZipCode']:''),
                    'Company'       =>  (array_key_exists('Company',$shipping_array)?$shipping_array['Company']:''),
                    'Country'       =>  $shipping_array['Country'],
                    'Street'        =>  $shipping_array['Address'],
                    'City'          =>  $shipping_array['City'],
                    'Region'        =>  (array_key_exists('Region',$shipping_array)?$shipping_array['Region']:''),
                    'AddressType'   =>  'shipping'
                 );

                $ItemResult=$this->db->insert_batch('order_shipping_billing_address', $OrderElements);


                     $this->db->insert('crud_history',array(
                                            'CrudAction'=>'add',
                                            'EntityType'=>'curl',
                                            'EntityId'=>0,
                                            'InsertedBy'=>'api',
                                            'CrudData'=>'Order added from operation Order data is '.json_encode($order_array)
                                         )
                                ); 
       
                    echo "Success";
        
    }

    function splitOrderSubmit($parent_order_id)
    {

                 ///SESSION INFORMATION
          $session                =   $this->session->userdata('logged_in');
          $added_by               =   $session['user_full_name'];
          $added_by_id            =   $session['user_id'];



        $parent_order_object = $this->getOrderTableInfoById($parent_order_id);
        $parent_order_items  = $this->getItemsByOrderId($parent_order_id);

        $parent_grand_total      = $parent_order_object->TotalAmount;
        $parent_grand_total_base = $parent_order_object->BaseTotalAmount;
        
        $included_items = $this->input->post('included_items');

        $shipping_charges = $this->input->post('shipping_charges');
        $cod_charges      = $this->input->post('cod_charges');
        $discount_amount  = $this->input->post('discount_amount');
        $grand_total      = $this->input->post('grand_total');
        $new_order_date   = $this->input->post('new_order_date');


        $shipping_email      = $this->input->post('shipping_email');
        $shipping_first_name =  $shipping_full_name  =  $this->input->post('shipping_full_name');
        $shipping_full_name_array   = explode(' ',$shipping_full_name);
        $shipping_last_name  = '';

        if(count($shipping_full_name_array)>1)
        {
            $shipping_first_name    = $shipping_full_name_array[0];
            $shipping_last_name     = $shipping_full_name_array[1];
        }

        $shipping_phone     =  $this->input->post('shipping_phone');
        $shipping_country   =  $this->input->post('shipping_country');
        $shipping_street    =  $this->input->post('shipping_street');
        $shipping_city      =  $this->input->post('shipping_city');


        $billing_first_name     = 
        $billing_full_name      =      $this->input->post('billing_full_name');
        $billing_full_name_array= explode(' ',$shipping_full_name);
        $billing_last_name      = '';

        if(count($billing_full_name_array)>1)
        {
            $billing_first_name    = $billing_full_name_array[0];
            $billing_last_name     = $billing_full_name_array[1];
        }


        $billing_email      =   $this->input->post('billing_email');
        $billing_phone      =   $this->input->post('billing_phone');
        $billing_country    =   $this->input->post('billing_country');
        $billing_street     =   $this->input->post('billing_street');
        $billing_city       =   $this->input->post('billing_city');
        $request_key        =   $this->input->post('request_key');


            //CHECKING IF CUSTOMER WITH THIS NAME EXIST OR NOT WE WILL CONSIDER CUSTOMER WITH BILLING INFO
            $customer_id  =  $this->updateCustomer(array('FirstName'=>$shipping_first_name,'LastName'=>$shipping_last_name,'Email'=>$shipping_email));

            $ExchangeRate = $parent_order_object->ExchangeRate;

            $new_order_reference_number = $parent_order_object->OrderReferenceNumber.'-'.$this->getTotalOrdersByParentId($parent_order_id);

             $order_insert_array = array(
                'OrderReferenceNumber' =>  $new_order_reference_number,
                'PaymentMethod'        =>  $parent_order_object->PaymentMethod,
                'OrderStatus'          =>  $parent_order_object->OrderStatus,
                'CustomerId'           =>  $customer_id,
                'CustomerCurrency'     =>  $parent_order_object->CustomerCurrency,
                'StoreCurrency'        =>  $parent_order_object->StoreCurrency,
                'ShippingCharges'      =>  $shipping_charges,
                'BaseShippingCharges'  =>  $shipping_charges/$ExchangeRate,
                'TotalAmount'          =>  $grand_total,
                'BaseTotalAmount'      =>  $grand_total/$ExchangeRate,
                'Discount'             =>  $discount_amount,
                'BaseDiscount'         =>  $discount_amount/$ExchangeRate,
                'CodCharges'            =>  $cod_charges,
                'BaseCodCharges'        =>  $cod_charges/$ExchangeRate,
                'TotalOrderItems'      =>  count($included_items),
                'OrderDate'            =>  date('Y-m-d',strtotime($new_order_date)),
                'OrderSource'          =>  $parent_order_object->OrderSource,
                'OrderSourceId'        =>  '0',
                'StationId'            =>  '1',
                'IsChild'              =>  '1',
                'ParentId'             =>  $parent_order_id,
                'ExchangeRate'         =>  $parent_order_object->ExchangeRate,

                'InsertedBy'            => $added_by,
                'InsertedById'          => $added_by_id,
                'RequestKey'            => $request_key
                );

/*                echo "<pre>";
                    print_r($order_insert_array);
                echo "</pre>";*/

                $this->db->insert('orders',$order_insert_array);
                $order_id= $this->db->insert_id();






        $this->load->model('product_model');
        $this->load->model('stock_model');
        
        $parent_item_count=0;
                foreach ($parent_order_items as $item_array)
                {   
                    $item_array= (array)$item_array;
                    $order_item_id  =  $item_array['OrderItemId'];
                    $sku            =  $item_array['ItemSku'];
                    $name           =  $item_array['ItemTitle'];
                    $order_qty      =  $item_array['OrderdQty'];
                    $price          =  $item_array['ItemPrice'];
                    $base_price     =  $item_array['BasePrice'];
                    $subtotal       =  $item_array['Subtotal'];
                    $base_subtotal  =  $item_array['BaseSubtotal'];

                    $item_object     =  $this->product_model->getProductInfoBySku($sku);
                    $item_stock_info =  $this->stock_model->getStockInfoBySku($sku);

                    


                     $bin_qty_array= array();

                            $total_stock_qty=0;
                            foreach ($item_stock_info as $key => $value) {
                                
                                if(count($value)>0)
                                {
                                    $bin_qty_array[$value['StockLocationTitle']] = $value['Qty'];
                                    $total_stock_qty+=$value['Qty'];
                                }
                            }


                    if(in_array($order_item_id,$included_items))
                    {

                   

                          $order_item_array[] = array(
                                    'sku'               =>  $sku,
                                    'thumbnail'         =>  $item_object->Thumbnail,
                                    'order_qty'         =>  $order_qty,
                                    'name'              =>  $name,
                                    'bin_qty'           =>  $bin_qty_array,
                                    'total_stock_qty'   =>  $total_stock_qty
                                );

                        $ItemElements[] = array(
                            'OrderId'        =>    $order_id,
                            'ItemSku'        =>    $sku,
                            'ItemTitle'      =>    $name,
                            'ItemPrice'      =>    $price,
                            'BasePrice'      =>    $base_price,
                            'Subtotal'       =>    $subtotal,
                            'BaseSubtotal'   =>    $base_subtotal,
                            'OrderdQty'      =>    $order_qty,
                            'RemainingItem'  =>    $order_qty
                        );
                    
                        $parent_grand_total-=$subtotal;
                        $parent_grand_total_base-=$base_subtotal;    

                        $this->db->where('OrderItemId',$order_item_id);
                        $this->db->delete('order_items');
                    }///end if array 
                    else
                    {
                          $parent_order_item_array[] = array(
                                    'sku'               =>  $sku,
                                    'thumbnail'         =>  $item_object->Thumbnail,
                                    'order_qty'         =>  $order_qty,
                                    'name'              =>  $name,
                                    'bin_qty'           =>  $bin_qty_array,
                                    'total_stock_qty'   =>  $total_stock_qty
                                );
                        $parent_item_count++;
                    }




                }
                //print_r($ItemElements);

                    $omigo_order_number = generate_omigo_order_number($order_id);

                    $this->db->where('OrderId',$order_id);
                    $this->db->update('orders',array('OrderNumber'=>$omigo_order_number,'ItemsInfo'=>json_encode($order_item_array)));

                    $ItemResult=$this->db->insert_batch('order_items', $ItemElements);


                ///UPDATE AMOUNT OF PARENT ORDER 

                    $this->db->where('OrderId',$parent_order_id);
                    $this->db->update('orders',
                        array(
                        'TotalAmount'=>$parent_grand_total,
                        'BaseTotalAmount'=>$parent_grand_total_base,
                        'TotalOrderItems'=>$parent_item_count,
                        'ItemsInfo'=>json_encode($parent_order_item_array)
                        ));






    //BILLING ADDRESS  HERE WE NEED TO ADD 1 MORE CONDITON IF SKU IS NOT PRESENT IN OMIGO WE NEED TO IMPORT THIS PRODUCT FIRST IN OMIGO THEN WE CAN USE THIS PRODUCT          
            $OrderElements = array();

                $OrderElements[] = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $billing_first_name,
                    'MiddleName'    =>  '',
                    'LastName'      =>  $billing_last_name,
                    'Email'         =>  $billing_email,
                    'Telephone'     =>  $billing_phone,
                    'Country'       =>  $billing_country,
                    'Street'        =>  $billing_street,
                    'City'          =>  $billing_city,
                    'AddressType'   =>  'billing'
                 );

                //SHIPPING ADDRESS               
                $OrderElements[] = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $shipping_first_name,
                    'MiddleName'    =>  '',
                    'LastName'      =>  $shipping_last_name,
                    'Email'         =>  $shipping_email,
                    'Telephone'     =>  $shipping_phone,
                    'Country'       =>  $shipping_country,
                    'Street'        =>  $shipping_street,
                    'City'          =>  $shipping_city,
                    'AddressType'   =>  'shipping'
                 );

                $ItemResult=$this->db->insert_batch('order_shipping_billing_address', $OrderElements);


                     $this->db->insert('crud_history',array(
                                            'CrudAction'=>'add',
                                            'EntityType'=>'curl',
                                            'EntityId'=>0,
                                            'InsertedBy'=>'api',
                                            'CrudData'=>'Split Order added from operation Order data is '.json_encode($_POST).' OrderReferenceNumber='.$new_order_reference_number
                                         )
                                ); 
       
            
$status='1';
                         $message=('<div class="box-body">
                     
                      <div class="alert alert-danger alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h4>    <i class="icon fa fa-check"></i> Success !</h4> New Order Is Created with Order '.$new_order_reference_number.'
                       
                      </div>
                    </div>');

                   return array('status'=>$status,'message'=>$message,'OrderId'=>$order_id);

    }










    function editSubmit()
    {

                 ///SESSION INFORMATION
          $session                =   $this->session->userdata('logged_in');
          $added_by               =   $session['user_full_name'];
          $added_by_id            =   $session['user_id'];

          $parent_order_id =  $this->input->post('order_id');
        
        $parent_order_object = $this->getOrderTableInfoById($parent_order_id);

        
        $included_items = $this->input->post('item_sku');

        $shipping_charges = $this->input->post('shipping_charges');
        $cod_charges      = $this->input->post('cod_charges');
        $discount_amount  = $this->input->post('discount_amount');
        $grand_total      = $this->input->post('grand_total');
        $new_order_date   = $this->input->post('new_order_date');


        $shipping_email      = $this->input->post('shipping_email');
        $shipping_first_name =  $shipping_full_name  =  $this->input->post('shipping_full_name');
        $shipping_full_name_array   = explode(' ',$shipping_full_name);

      
        $shipping_last_name  = '';
        if(count($shipping_full_name_array)>1)
        {
            $shipping_first_name    = $shipping_full_name_array[0];
            $shipping_last_name     = $shipping_full_name_array[1];
        }

        $shipping_phone     =  $this->input->post('shipping_phone');
        $shipping_country   =  $this->input->post('shipping_country');
        $shipping_street    =  $this->input->post('shipping_street');
        $shipping_city      =  $this->input->post('shipping_city');


        $billing_first_name     = 
        $billing_full_name      =      $this->input->post('billing_full_name');
        $billing_full_name_array= explode(' ',$billing_full_name);
        $billing_last_name      = '';

        if(count($billing_full_name_array)>1)
        {
            $billing_first_name    = $billing_full_name_array[0];
            $billing_last_name     = $billing_full_name_array[1];
        }

        $billing_email      =   $this->input->post('billing_email');
        $billing_phone      =   $this->input->post('billing_phone');
        $billing_country    =   $this->input->post('billing_country');
        $billing_street     =   $this->input->post('billing_street');
        $billing_city       =   $this->input->post('billing_city');


        $preferred_delivery_date       =   $this->input->post('preferred_delivery_date');
        $preferred_delivery_time       =   $this->input->post('preferred_delivery_time');        

        



        $request_key        =   $this->input->post('request_key');


            //CHECKING IF CUSTOMER WITH THIS NAME EXIST OR NOT WE WILL CONSIDER CUSTOMER WITH BILLING INFO
            $customer_id  =  $this->updateCustomer(array('FirstName'=>$shipping_first_name,'LastName'=>$shipping_last_name,'Email'=>$shipping_email));

            $ExchangeRate = $parent_order_object->ExchangeRate;


             $order_update_array = array(
                'CustomerId'           =>  $customer_id,
                'ShippingCharges'      =>  $shipping_charges,
                'BaseShippingCharges'  =>  $shipping_charges/$ExchangeRate,
                'TotalAmount'          =>  $grand_total,
                'BaseTotalAmount'      =>  $grand_total/$ExchangeRate,
                'Discount'             =>  $discount_amount,
                'BaseDiscount'         =>  $discount_amount/$ExchangeRate,
                'CodCharges'           =>  $cod_charges,
                'BaseCodCharges'       =>  $cod_charges/$ExchangeRate,
                'TotalOrderItems'      =>  count($included_items),
                'RequestKey'            => $request_key
                );

   /*             echo "<pre>";
                    print_r($order_update_array);
                echo "</pre>";*/

                $order_id = $parent_order_id;

                $this->db->where('OrderId',$order_id);
                $this->db->update('orders',$order_update_array);





        $this->load->model('product_model');
        $this->load->model('stock_model');
        

        $item_price = $this->input->post('item_price');
        $item_qty   = $this->input->post('item_qty');

        $parent_item_count=0;
                foreach ($included_items as $post_item_key => $sku)
                {   
                    $item_object    =  (array)$this->product_model->getProductInfoBySku($sku);                    
                    
                    $item_id        =  $item_object['ProductId'];
                    $sku            =  $item_object['Sku'];
                    $name           =  $item_object['Title'];
                    $order_qty      =  $item_qty[$post_item_key];
                    $price          =  $item_price[$post_item_key];
                    $base_price     =  $price/$ExchangeRate;
                    $subtotal       =  $price*$item_qty[$post_item_key];
                    $base_subtotal  =  $base_price*$item_qty[$post_item_key];

              
                    $item_stock_info =  $this->stock_model->getStockInfoBySku($sku);


                     $bin_qty_array= array();

                    $total_stock_qty=0;
                    foreach ($item_stock_info as $key => $value) {
                        
                        if(count($value)>0)
                        {
                            $bin_qty_array[$value['StockLocationTitle']] = $value['Qty'];
                            $total_stock_qty+=$value['Qty'];
                        }
                    }



                      $order_item_array[] = array(
                                'sku'               =>  $sku,
                                'thumbnail'         =>  $item_object['Thumbnail'],
                                'order_qty'         =>  $order_qty,
                                'name'              =>  $name,
                                'bin_qty'           =>  $bin_qty_array,
                                'total_stock_qty'   =>  $total_stock_qty
                            );

                        $ItemElements[] = array(
                            'OrderId'        =>    $order_id,
                            'ItemSku'        =>    $sku,
                            'ItemTitle'      =>    $name,
                            'ItemPrice'      =>    $price,
                            'BasePrice'      =>    $base_price,
                            'Subtotal'       =>    $subtotal,
                            'BaseSubtotal'   =>    $base_subtotal,
                            'OrderdQty'      =>    $order_qty,
                            'RemainingItem'  =>    $order_qty
                        );
                    

                }


                    ///DELETE PREVIOUS ITEMFIRST
                    $this->db->where('OrderId',$order_id);
                    $this->db->delete('order_items');

                    ///INSERT NEW ITEMS
                    $this->db->where('OrderId',$order_id);
                    $this->db->update('orders',array('ItemsInfo'=>json_encode($order_item_array)));

                    $ItemResult=$this->db->insert_batch('order_items', $ItemElements);

    
        




    //BILLING ADDRESS  HERE WE NEED TO ADD 1 MORE CONDITON IF SKU IS NOT PRESENT IN OMIGO WE NEED TO IMPORT THIS PRODUCT FIRST IN OMIGO THEN WE CAN USE THIS PRODUCT          
            $OrderElements = array();

                $OrderElements = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $billing_first_name,
                    'MiddleName'    =>  '',
                    'LastName'      =>  $billing_last_name,
                    'Email'         =>  $billing_email,
                    'Telephone'     =>  $billing_phone,
                    'Country'       =>  $billing_country,
                    'Street'        =>  $billing_street,
                    'City'          =>  $billing_city,
                    'AddressType'   =>  'billing'
                 );

                $this->db->where('OrderId',$order_id);
                $this->db->where('AddressType','billing');
                $this->db->update('order_shipping_billing_address',$OrderElements);

                //SHIPPING ADDRESS               
                $OrderElements = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $shipping_first_name,
                    'MiddleName'    =>  '',
                    'LastName'      =>  $shipping_last_name,
                    'Email'         =>  $shipping_email,
                    'Telephone'     =>  $shipping_phone,
                    'Country'       =>  $shipping_country,
                    'Street'        =>  $shipping_street,
                    'City'          =>  $shipping_city,
                    'DeliveryDate'  => date('Y-m-d',strtotime($preferred_delivery_date)),
                    'DeliveryTime'  => $preferred_delivery_time,
                    'AddressType'   =>  'shipping'
                 );

                $this->db->where('OrderId',$order_id);
                $this->db->where('AddressType','shipping');
                $this->db->update('order_shipping_billing_address',$OrderElements);


           
                $status='1';
                     $message=('<div class="box-body">
                 
                  <div class="alert alert-success alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h4>    <i class="icon fa fa-check"></i> Success !</h4> Order Is Updated 
                   
                  </div>
                </div>');

             return array('status'=>$status,'message'=>$message,'OrderId'=>$order_id);

    }





}
    ?>