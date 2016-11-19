<?php

class Rest_model extends CI_Model {


    function __construct()
    {
        //Call the Model constructor
        parent::__construct();
        $this->load->model('product_model');
        $this->load->model('stock_model');
    $this->load->model('order_model');
          
    }


    public function rest_get_and_add_kingosuq_order($orderId){

            $userData = array("username" => "api", "password" => "vajidabid123");
            $ch = curl_init("http://kingsouq.com/rest/V1/integration/admin/token");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen(json_encode($userData))));
             
             $token = curl_exec($ch);


            $ch = curl_init("http://www.kingsouq.com/rest/default/V1/orders/$orderId");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));
 
            $result     = curl_exec($ch);
            $response   = json_decode($result,true);

                
                //print_r(json_encode($response));
                $result='';
                if(!array_key_exists('message',$response))
                    {
                        $result=$response;
                        $this->addOrder($result);
                    }

            $this->db->insert('crud_history',array(
                                        'CrudAction'=>'add',
                                        'EntityType'=>'curl',
                                        'EntityId'=>0,
                                        'InsertedBy'=>'api',
                                        'CrudData'=>'Order Added By KingSouq.com API Order Id is '.$orderId
                                     )
                            );   


     return $result;

}    



function post_to_operation($order_array,$payment_method)
{


    if($payment_method=='cod')
    {
        $payment_method=='COD';
    }
    if($payment_method=='paypal')
    {
        $payment_method=='Paypal';
    }
    if($payment_method=='paytabs')
    {
        $payment_method='Paytabs';
    }
    if($payment_method=='ccavenue')
    {
        $payment_method='Ccavenue';
    }

    if($order_array['status']=='processing')
    {
        $final_status = 'Confirmed';
    }

    if($order_array['status']=='pending')
    {
        $final_status = 'Created';
    }

 
    //SHIPPING ADDRESS               
    $shipping_array = $order_array['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

    $option=array();
    $option['order_type']           =  'Kingsouq';
    $option['order_no']             =  $order_array['increment_id'];
    $option['order_datetime']       =  date('d-m-Y h:m',strtotime($order_array['created_at']));
    $option['remarks']              =  '';
    $option['payment_mode']         =  $payment_method;
    $option['currency']             =  $order_array['order_currency_code'];
    $option['shipping_amount']      =  $order_array['shipping_amount'];
    $option['discount_amount']      =  $order_array['discount_amount'];
    $option['cod_amount']           =  '0';
    $option['order_value']          =  $order_array['grand_total'];
    $option['awb_number']           =  '';
    $option['cnee_name']            =  $shipping_array['firstname'].''.$shipping_array['lastname'];
    $option['cnee_email']           =  $shipping_array['email'];
    $option['cnee_mobile']          =  $shipping_array['telephone'];
    $option['cnee_phone']           =  '';
    $option['cnee_addrs1']          =  implode(',',$shipping_array['street']);
    $option['cnee_addrs2']          =  '';
    $option['cnee_addrs3']          =  '';
    $option['cnee_zip']             =  $shipping_array['postcode'];
    $option['cnee_country_code']    =  $shipping_array['country_id'];
    $option['cnee_country_name']    =  $shipping_array['country_id'];
    $option['consignee_city']       =  $shipping_array['city'];
    $option['oo_ovl_status']        =  $final_status;
    $option['address_info']         =  implode(',',$shipping_array['street']);
    $option['created_by']           =  'auto-api';
    $option['add_other_order']      =  'true';    


        foreach ($order_array['items'] as $item_array) {
            $option['item_sku'][] = $item_array['sku'];
            $option['item_desc'][] = $item_array['name'];
            $option['item_qty'][] = $item_array['qty_ordered'];
            $option['item_price'][] = $item_array['row_total']/$item_array['qty_ordered'];
            $option['item_amount'][] = $item_array['row_total'];
        }




    http_build_query_for_curl( $option, $post );

/*print_r($post);*/



        $ch = curl_init("http://operation.kingsouq.com/auto_add_other_order.php");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         
             
             $response = curl_exec($ch);

echo "<pre>";
    print_r($response);
echo "</pre>";  

}


function addOrder($order_array)
{
    $this->load->model('order_model');
          
            $payment_method = get_payment_status($order_array['payment']['method']);
            $order_status   = get_order_status($order_array['status']);

            if($order_status=='confirmed')
            {
                echo "working..";

                $billing_address_array=array(
                        'FirstName' =>  $order_array['billing_address']['firstname'],
                        'LastName' =>  $order_array['billing_address']['lastname'],
                        'Email'     =>  $order_array['billing_address']['email']
                    );
          //$this->post_to_operation($order_array,$payment_method);

            //CHECKING IF CUSTOMER WITH THIS NAME EXIST OR NOT WE WILL CONSIDER CUSTOMER WITH BILLING INFO
            $customer_id =  $this->order_model->updateCustomer($billing_address_array,$payment_method);

            $order_insert_array = array(
                'OrderReferenceNumber' =>  $order_array['increment_id'],
                'OrderNumber'          =>  $order_array['increment_id'],
                'PaymentMethod'        =>  $payment_method,
                'OrderStatus'          =>  $order_status,
                'ShipmentStatus'       =>  'ready-to-book',
                'CustomerId'           =>  $customer_id,
                'CustomerCurrency'     =>  $order_array['order_currency_code'],
                'StoreCurrency'        =>  $order_array['base_currency_code'],
                'ShippingCharges'      =>  $order_array['shipping_amount'],
                'BaseShippingCharges'  =>  $order_array['base_shipping_amount'],
                'TotalAmount'          =>  $order_array['grand_total'],
                'BaseTotalAmount'      =>  $order_array['base_grand_total'],
                'Discount'             =>  $order_array['discount_amount'],
                'BaseDiscount'         =>  $order_array['base_discount_amount'],
                'TotalOrderItems'      =>  count($order_array['items']),
                'OrderDate'            =>  $order_array['created_at'],
                'OrderSource'          =>  'KINGSOUQ.COM',
                'OrderSourceId'        =>  '1',
                'StationId'            =>  '1',
                'ExchangeRate'         => $order_array['base_to_order_rate'],
                'InsertedBy'           => 'auto-api',
                'InsertedById'         => 0
                );


                $this->db->insert('orders',$order_insert_array);
                $order_id= $this->db->insert_id();

     

                //INSERT ITEMS 
                $ItemElements=array();
                $order_item_array=array();
                $parent_item_array=array();

                foreach ($order_array['items'] as $item_array) {

                    $sku = $item_array['sku'];

/*                    echo "<pre>";

                    print_r($item_array);

                    echo "</pre>";*/

                    if($item_array['product_type']=='simple')
                    {
                        if(array_key_exists('parent_item',$item_array))
                        {
                            $parent_item_sku = $item_array['parent_item']['sku'];
                            $parent_item_sku_array = explode('-',$parent_item_sku); 
                            $total_child_items = count($parent_item_sku_array)-1; 
                            $item_array['price'] = ($item_array['parent_item']['price']/$total_child_items);
                            $item_array['base_price'] = ($item_array['parent_item']['base_price']/$total_child_items);


                            $item_array['row_total']      = ($item_array['price']*$item_array['qty_ordered']);
                            $item_array['base_row_total'] = ($item_array['base_price']*$item_array['qty_ordered']);

                        }

/*
                        //SOMETIME SIMPLE PRODUCT COME WITH MULTIPLE SKUS IN SINGLE ITEM SO WE NEED TO DEVIDE PRICE TO TOTAL ITEMS
                         $skuCount = explode('-',$item_array['sku']);
                        if(count($skuCount))
                        {
                            $skuCount = ;
                        }*/

                        $item_object     =  $this->product_model->getProductInfoBySku($sku);
                        $item_stock_info =  $this->stock_model->getStockInfoBySku($sku);
                        $order_qty       =  $item_array['qty_ordered'];
                        $name            =  $item_array['name'];


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
                                'sku'=>$sku,
                                'thumbnail'=>$item_object->Thumbnail,
                                'order_qty'=>$order_qty,
                                'name'=>$name,
                                'bin_qty'=>$bin_qty_array,
                                'total_stock_qty'=>$total_stock_qty
                            );


                        $ItemElements[] = array(
                        'OrderId'     =>    $order_id,
                        'ItemSku'     =>    str_replace(' ','', $sku),
                        'ItemTitle'   =>    $name,
                        'ItemPrice'   =>    $item_array['price'],
                        'BasePrice'   =>    $item_array['base_price'],
                        'Subtotal'    =>    $item_array['row_total'],
                        'BaseSubtotal'=>    $item_array['base_row_total'],
                        'OrderdQty'   =>    $order_qty,
                        'OrderDate'   =>    $order_array['created_at'],
                        'Thumbnail'   =>    $item_object->Thumbnail,
                        'Weight'      =>    (array_key_exists('weight',$item_array)?$item_array['weight']:''),
                        'OrderStatus'          =>  $order_status,
                        'ShipmentStatus'       =>  'ready-to-book'
                        );

                    
                    }


                }

                echo "<pre>";
                    print_r($order_item_array);
                echo "</pre>";

                $omigo_order_id = generate_omigo_order_number($order_id);

                $this->db->where('OrderId',$order_id);
                $this->db->update('orders',array('OrderNumber'=>$omigo_order_id,'ItemsInfo'=>json_encode($order_item_array)));

               $ItemResult=$this->db->insert_batch('order_items', $ItemElements);
             

                //BILLING ADDRESS  HERE WE NEED TO ADD 1 MORE CONDITON IF SKU IS NOT PRESENT IN OMIGO WE NEED TO IMPORT THIS PRODUCT FIRST IN OMIGO THEN WE CAN USE THIS PRODUCT          
                $billing_array = $order_array['billing_address'];

                $OrderElements = array();

                $OrderElements[] = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $billing_array['firstname'],
                    'MiddleName'    =>  '',
                    'LastName'      =>  $billing_array['lastname'],
                    'Email'         =>  $billing_array['email'],
                    'Telephone'     =>  $billing_array['telephone'],
                    'Fax'           =>  '',
                    'Company'       =>  (array_key_exists('company',$billing_array)?$billing_array['company']:''),
                    'Country'       =>  $billing_array['country_id'],
                    'Street'        =>  implode(',',$billing_array['street']),
                    'City'          =>  $billing_array['city'],
                    'Region'        =>   (array_key_exists('region',$billing_array)?$billing_array['region']:''),
                    'AddressType'   =>  'billing'
                 );


                
                //SHIPPING ADDRESS               
                $shipping_array = $order_array['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

                $OrderElements[] = array(
                    'OrderId'       =>  $order_id,
                    'FirstName'     =>  $shipping_array['firstname'],
                    'MiddleName'    =>  '',
                    'LastName'      =>  $shipping_array['lastname'],
                    'Email'         =>  $shipping_array['email'],
                    'Telephone'     =>  $shipping_array['telephone'],
                    'Fax'           =>  '',
                    'Company'       =>  (array_key_exists('company',$shipping_array)?$shipping_array['company']:''),
                    'Country'       =>  $shipping_array['country_id'],
                    'Street'        =>  implode(',',$shipping_array['street']),
                    'City'          =>  $shipping_array['city'],
                    'Region'        =>  (array_key_exists('region',$shipping_array)?$shipping_array['region']:''),
                    'AddressType'   =>  'shipping'
                 );

                $ItemResult=$this->db->insert_batch('order_shipping_billing_address', $OrderElements);
            
            }
           

             
            
}





}
    ?>