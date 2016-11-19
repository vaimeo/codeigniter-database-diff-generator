<?php
Class product_model extends CI_Model
{



function getProductInfoBySkuJson($q,$ClauseField,$limit=false,$MaxRows)
{
      $ClauseField=explode(',',$ClauseField);

        $Cond="";
        foreach ($ClauseField as $ClauseFieldkey => $ClauseFieldvalue) {
            if($ClauseFieldkey==0){
                  $Cond = " where $ClauseFieldvalue like '%$q%'";
              }else{
                $Cond.=" OR  $ClauseFieldvalue like '%$q%'";
              }
        }
        $limit_cond='';
        if($limit==true){
            $limit_cond="limit 0,10";
        }


       $product_path = base_url();
//echo "select Sku,Title as Description,Cost,AvailableQty,CONCAT('$product_path/',Thumbnail) as Thumbnail from products  $Cond";
    $query = $this->db->query("select Sku,Title as Description,Cost,AvailableQty,CONCAT('$product_path/',Thumbnail) as Thumbnail from products  $Cond $limit_cond");
    $d = $query->result();
    $array=array();
    if($query->num_rows()>0){
        foreach ($d as $key => $value) {
            //$value->Description=$this->getplaintextintrofromhtml($value->Description,200);
            $array[]=   $value;
        }
        
    }else ///SEARCH PRODUCT FROM REST API AND ADD IT IN SYSTEM 
    {
         $array[] =    $this->restMagento2GetProduct($q);
    }
    header('Content-Type: application/json');
    return json_encode($array);
}



function getLastSkuSequence($skuPart)
{

       $q ="select Sku from products where Sku Like '%$skuPart%' Order By ProductId DESC limit 0,1";
        $query = $this->db->query($q);
 

        if($query->num_rows()>0)
        {
            $d= $query->row();
            $d = $d->Sku;
              $d = substr($d,5,6)+1;


        }else
        {
            $d='0';
        }

        return (int)$d;
}




function getProductInfoBySkuJsonSelect2($q,$ClauseField,$limit=false,$MaxRows)
{
      $ClauseField=explode(',',$ClauseField);

        $Cond="";
        foreach ($ClauseField as $ClauseFieldkey => $ClauseFieldvalue) {
            if($ClauseFieldkey==0){
                  $Cond = " where $ClauseFieldvalue like '%$q%'";
              }else{
                $Cond.=" OR  $ClauseFieldvalue like '%$q%'";
              }
        }
        $limit_cond='';
        if($limit==true){
            $limit_cond="limit 0,10";
        }


       $product_path = base_url();
//echo "select Sku,Title as Description,Cost,AvailableQty,CONCAT('$product_path/',Thumbnail) as Thumbnail from products  $Cond";
    $query = $this->db->query("select Sku as id ,Title as text ,Price as price,SpecialPrice as special_price,Currency as currency,ExchangeRate as exchange_rate,Cost as cost,AvailableQty as avl_qty,CONCAT('$product_path/',Thumbnail) as thumbnail from products  $Cond $limit_cond");
    $d = $query->result();
    $array=array();
    if($query->num_rows()>0){
        foreach ($d as $key => $value) {
            //$value->Description=$this->getplaintextintrofromhtml($value->Description,200);
            $array[]=   $value;
        }
        
    }else ///SEARCH PRODUCT FROM REST API AND ADD IT IN SYSTEM 
    {
         $array[] =    $this->restMagento2GetProduct($q);
    }
    header('Content-Type: application/json');
    return json_encode($array);
}





    public function restMagento2GetProduct($getSku){

            $userData = array("username" => "api", "password" => "vajidabid123");
            $ch = curl_init("http://kingsouq.com/rest/V1/integration/admin/token");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen(json_encode($userData))));
             
            $token = curl_exec($ch);

            $ch = curl_init("http://kingsouq.com/rest/V1/products/$getSku");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));
 
                $result     = curl_exec($ch);
                $response   = json_decode($result,true);

             
                $type_id='';
                if(!array_key_exists('message',$response))
                    {
                        if($response['type_id']=='simple'||$response['type_id']=='bundle'){

                            $product_sku=$response['sku'];
                            $product_name=$response['name'];
                            $cost = 0.00;
                            $product_price = $response['price'];
                            
                             $thumb = 'http://cdn.kingsouq.com/pub/media/catalog/product'.m2_rest_product_get_custom_attribute_val($response['custom_attributes'],'thumbnail');

                            
                            $item=array('Sku'=>$response['sku'],'Description'=>$response['name'],'Cost'=>0,'Thumb'=>'');

                            
                            $product_url=m2_rest_product_get_custom_attribute_val($response['custom_attributes'],'url_key').'.html';
                            
                            $product_description=m2_rest_product_get_custom_attribute_val($response['custom_attributes'],'description');
                            
                            $product_id = $this->restSubmit(
                                        $product_sku,
                                        $product_price,
                                        $product_name,
                                        $product_url,
                                        $product_description
                                        );
                            $this->copy_images($product_id,array($thumb));
                            $this->load->model('barcode_model');
                            $this->barcode_model->barcode_genrate_and_save($product_sku);                            
 
                        }

                 
                }
                else
                {
                       $product_id = $this->restSubmit(
                                        $getSku,
                                        '',
                                        '',
                                        '',
                                        ''
                                        );
                            $this->load->model('barcode_model');
                            $this->barcode_model->barcode_genrate_and_save($getSku);  
                }

     return $item;

}




 function copy_images($ProductId,$ImagesArray)
    {
        $images_db_array = $db_img_array = array();

        if(count($ImagesArray)>0)
        {
            foreach ($ImagesArray as $key => $value) {
                    $db_img_array_names[] = $image_name =  $ProductId.'_'.$key.'_'.md5(rand(0,1000));
                    $db_img_array[] = $ImagePath = 'uploads/products/images/'.$image_name.'.png';
                    $images_db_array[] = array(
                            'RecordFor'=>'products',
                            'RecordForId'=>$ProductId,
                            'ImagePath'=>$ImagePath
                            );
                }
             $this->db->insert_batch('gallery', $images_db_array); 
        }

        foreach ($db_img_array as $key => $value) {
                file_put_contents($value, file_get_contents($ImagesArray[$key]));
        }

        $thumb_name   = $db_img_array_names[0];
        $image_thumb  = 'uploads/products/images/thumb/'.$thumb_name.'.png';

        $this->load->library('image_lib');
        $config['image_library']    = 'gd2';
        $config['source_image']     = $db_img_array[0];
        $config['new_image']        = $image_thumb;     
        $config['create_thumb']     = TRUE;
        $config['maintain_ratio']   = TRUE;
        $config['width']            = 75;
        $config['height']           = 75;

        $this->image_lib->clear();
        $this->image_lib->initialize($config);
        $this->image_lib->resize();

        $this->db->where('ProductId',$ProductId);
        $this->db->update('products',array('Thumbnail'=>'uploads/products/images/thumb/'.$thumb_name.'_thumb.png'));
    }



    function getplaintextintrofromhtml($html, $numchars) {
        // Remove the HTML tags
        $html = strip_tags($html);
        // Convert HTML entities to single characters
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        // Make the string the desired number of characters
        // Note that substr is not good as it counts by bytes and not characters
        $html = mb_substr($html, 0, $numchars, 'UTF-8');
        // Add an elipsis
        $html .= "…";
        return $html;
    }


 function getProductInfoById($ProductId)
 {
        $this->db->where('ProductId',$ProductId);
        $this->db->select('*');
        return $this->db->get('products')->row(); 
 }



 function getProductInfoBySku($Sku)
 {
        $this->db->where('Sku',$Sku);
        $this->db->select('*');
        $query = $this->db->get('products'); 

        if($query->num_rows()>0)
        {
            $d= $query->row();
        }else
        {
            ///addding product to omigo from magento
            $this->restMagento2GetProduct($Sku);
            $this->db->where('Sku',$Sku);
            $this->db->select('*');
            $query = $this->db->get('products'); 

            if($query->num_rows()>0)
            {
                $d= $query->row();
            }
            
        }
        return $d;
 }





function getProductExcelExportDeta($WhereClause='',$ExportType='magento2')
{
    if($ExportType=='magento2')
    {
        ///CONFIG DATA OUTPUT TO EXCEL



        ///EXCEL FILE LABEL AND MY SQL CLUMNG NAME MAPPING
        /*

'ProductId' => SQL SELECT COLUM NAME LEAVE BLANK KEY IF NOT AN SQL COUMN
array( SQL CONFIG ARRAY
    'sql'=>'true', IF SQL COLUMN NAME THEN TRUE
    'excel' =>  EXCEL CONFIG ARRAY
    array(
        'label'=> EXCEL LABEL CONFIG KEY 
        'product_id',EXCEL LABEL VALUE
        'fill'=>EXCEL LABEL MUST BE FILL WITH DATA FROM DB SETT TRUE OR BLANK SET FALSE
        'true')
    ),
        */
        $column_config = array(
            'Sku'       =>  array('sql'=>'true','excel' => array('label'=>'SKU <sku>','auto_fill'=>'true')),
            'Categories' => array('sql'=>'true','excel' => array('label'=>'Categories Path <category_ids> (admin)','auto_fill'=>'true')),
            'VendorName' => array('sql'=>'true','excel' => array('label'=>'Supplier <supplier> (admin)','auto_fill'=>'true')),
            'Area'       => array('sql'=>'true','excel' => array('label'=>'Area <area> (admin)','auto_fill'=>'true')),
                            array('sql'=>'false','excel'=> array('label'=>'AED <aed> (admin)','auto_fill'=>'false','fill_text'=>'')),
                            array('sql'=>'false','excel'=> array('label'=>'Cost-Update Time <cost_price_time> (admin)','auto_fill'=>'false','fill_text'=>'')),
                            array('sql'=>'false','excel'=> array('label'=>'Special AED <special_aed> (admin)','auto_fill'=>'false','fill_text'=>'')),
            'SpecialPrice'=>array('sql'=>'true','excel' => array('label'=>'Special Price <special_price> (admin)','auto_fill'=>'true')),
            'Description'=> array('sql'=>'true','excel' => array('label'=>'Description <description> (admin)','auto_fill'=>'true')),
                            array('sql'=>'false','excel'=> array('label'=>'Stock Availability <is_in_stock> (admin)','auto_fill'=>'false','fill_text'=>'')),
            'AttributeSet'=>array('sql'=>'true','excel' => array('label'=>'Attribute Set Name <attribute_set_name>','auto_fill'=>'true')),
                            array('sql'=>'false','excel'=> array('label'=>'Status <status> (admin)','auto_fill'=>'false','fill_text'=>'0')),
            'Cost'     =>   array('sql'=>'true','excel' => array('label'=>'Cost <cost> (admin)','auto_fill'=>'true')),
                            array('sql'=>'false','excel'=> array('label'=>'Product Selling Point <product_selling_point> (admin)','auto_fill'=>'false','fill_text'=>'simple')),
            'AvailableQty'     =>    array('sql'=>'true','excel' => array('label'=>'Qty <qty> (admin)','auto_fill'=>'true')),
             array('sql'=>'false','excel'=> array('label'=>'(admin)','auto_fill'=>'false','fill_text'=>'')),
            );

        $select= $excel_head_array = array();
        foreach ($column_config as $key3 => $value3) {
            if($value3['sql']=='true'){
                $select[] = $key3;
            }
                $excel_head_array[]=$value3['excel']['label'];
        }
        
        $select_columns =  implode(',',$select);

   /*     $WhereClause = str_replace('andpercent', '%', $WhereClause);
*/
        $query = $this->db->query("select $select_columns from products WHERE 1=1 ".$WhereClause);
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






 function checkProdcutSkuExist($clause,$user_name)
 {
   


    $this->db->like($clause,$user_name);
    $this->db->from('products');
   return  $this->db->count_all_results();

   
 }




 function addSubmit()
 {
        ///BUSINESS PROCESS 
        //1 VENDOR WILL RESGISER HIMSELF AND GET EMAIL CONFIRMATION FOR HIS LOGIN INFORMATION 
        
        $product_sku            =       strtoupper($this->input->post('product_sku'));
        $vendor_name            =       $this->input->post('vendor_name');
        $vendor_id              =       $this->input->post('vendor_id');
        $product_cost           =       $this->input->post('product_cost');
        $product_price          =       $this->input->post('product_price');
        $product_special_price  =       $this->input->post('product_special_price');
        $product_name           =       $this->input->post('product_name');
        $product_url            =       $this->input->post('product_url');
        $product_description    =       $this->input->post('product_description');


        $p                  =       $this->input->post('product_area');
        $p                  =  explode('-',$p);
        $product_source_id  = $p[0];
        $product_source     = $product_area =  $p[1];


       
        $c                       =      $this->input->post('currency');
        $c                       =      explode('-',$c);
        $currency                =      $c[0];
        
        $exchange_rate = $this->currency_model->getUSDExchangeRate($currency);

        $c = $this->input->post('categories');
  

        if(count($c)>0)
        {
            $categories = $category_ids =array();
                foreach ($c as $key => $value) {
             $category_array = explode('-', $value);
             $category_ids[]=$category_array[0];
             $categories[]=$category_array[1];

            }
            $category_ids = implode(',',$category_ids);
            $categories = implode('|',$categories);

        }else
        {
                  $category_ids='';
                $categories='';
        }



        if($this->input->post('attribute_set'))
        {

        $as                 = explode('-', $this->input->post('attribute_set'));
            $attribue_set_id       =       $as[0];
            $attribue_set          =       $as[1];
        }else{
            $attribue_set_id       =       '';
            $attribue_set          =       '';

        }
        
        $session                =       $this->session->userdata('logged_in');
        $added_by               =       $session['user_full_name'];
        $added_by_id            =       $session['user_id'];
        //$station_currency       =       $session['station_currency'];

        //INSERT PRODUCT DATA 

        $insert_array = array(
            'VendorName'            =>  $vendor_name,
            'VendorId'              =>  $vendor_id, 
            'Sku'                   =>  $product_sku,
            'Title'                 =>  $product_name,
            'Description'           =>  $product_description,
            'Url'                   =>  $product_url,
            'Area'                  =>  $product_source,
            'ProductSourceId'       =>  $product_source_id,
            'ProductSource'         =>  $product_source,
            'Price'                 =>  ($product_price/$exchange_rate),
            'Cost'                  =>  ($product_cost/$exchange_rate),
            'SpecialPrice'          =>  ($product_special_price/$exchange_rate),
            'Currency'              =>  $currency,
            'ExchangeRate'          =>  $exchange_rate,
            'Categories'            =>  $categories,
            'AttributeSet'          =>  $attribue_set,
            'InsertedDate'          =>  date('y-m-d h:m:s'),
            'InsertedBy'            =>  $added_by,
            'InsertedById'          =>  $added_by_id
            );

        $this->db->insert('products',$insert_array);
        $product_id = $this->db->insert_id();

    $this->load->model('barcode_model');
    $this->barcode_model->barcode_genrate_and_save($product_sku);



     return $product_id;


 }







 function addBulkSubmit()
 {
        ///BUSINESS PROCESS 
        //1 VENDOR WILL RESGISER HIMSELF AND GET EMAIL CONFIRMATION FOR HIS LOGIN INFORMATION 
        
        $vendor_name            =       'default-vendor';
        $vendor_id              =       'DFV000';


        $product_sku            =       $this->input->post('product_sku');//array
        $product_cost           =       $this->input->post('product_cost');//array
        $product_price          =       $this->input->post('product_price');//array
        $product_special_price  =       $this->input->post('product_special_price');//array
        $product_name           =       $this->input->post('product_name');//array
       

        $p                  = $this->input->post('arr1');
        $p                  = explode('-',$p[0]);
        $product_source_id  = $p[2];
        $product_source     = $product_area =  $p[1];



        $c                       =      $this->input->post('currency');
        $c                       =      explode('-',$c);
        $currency                =      $c[0];
        
        $exchange_rate = $this->currency_model->getUSDExchangeRate($currency);

        $c = $this->input->post('arr3');
  

        if(count($c)>0)
        {
            $categories = $category_ids =array();
                foreach ($c as $key => $value) {
             $category_array = explode('-', $value);
             $category_ids[]=$category_array[0];
             $categories[]=$category_array[1];

            }
            $category_ids = implode(',',$category_ids);
            $categories = implode('|',$categories);

        }else
        {
                  $category_ids='';
                $categories='';
        }


        $attribue_set_id       =       '';
        $attribue_set          =       '';
        
        $session                =       $this->session->userdata('logged_in');
        $added_by               =       $session['user_full_name'];
        $added_by_id            =       $session['user_id'];
        //$station_currency       =       $session['station_currency'];


        $insert_array=array();

        foreach ($product_sku as $key => $value) {
          
        //INSERT PRODUCT DATA 

        $insert_array[] = array(
            'VendorName'            =>  $vendor_name,
            'VendorId'              =>  $vendor_id, 
            'Sku'                   =>  strtoupper($value),
            'Title'                 =>  $product_name[$key],
            'Area'                  =>  $product_source,
            'ProductSourceId'       =>  $product_source_id,
            'ProductSource'         =>  $product_source,
            'Price'                 =>  ($product_price[$key]/$exchange_rate),
            'Cost'                  =>  ($product_cost[$key]/$exchange_rate),
            'SpecialPrice'          =>  ($product_special_price[$key]/$exchange_rate),
            'Currency'              =>  $currency,
            'ExchangeRate'          =>  $exchange_rate,
            'Categories'            =>  $categories,
            'InsertedDate'          =>  date('y-m-d h:m:s'),
            'InsertedBy'            =>  $added_by,
            'InsertedById'          =>  $added_by_id
            );

            $this->load->model('barcode_model');
            $this->barcode_model->barcode_genrate_and_save($value);
        }


        $this->db->insert_batch('products',$insert_array);



     return true;


 }





function getProductSourcesListDd()
{
        $query = $this->db->query("SELECT ProductSourceTitle,ProductSourceId FROM `product_sources` ");
        $d = $query->result();
         $array=array();
      
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->ProductSourceId.'-'.$value->ProductSourceTitle]=$value->ProductSourceTitle;
            }
        }
        else{
            $array[0]='No Source Present';
        }
        return $array;
    }



function getBulkProductSourcesListDd()
{
        $query = $this->db->query("SELECT ProductSourceTitle,ProductSourceShort ,ProductSourceId FROM `product_sources` ");
        $d = $query->result();
         $array=array();
      
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->ProductSourceShort.'-'.$value->ProductSourceTitle.'-'.$value->ProductSourceId]=$value->ProductSourceTitle;
            }
        }
        else{
            $array[0]='No Source Present';
        }
        return $array;
    }






function getBrandListDd()
{
        $query = $this->db->query("SELECT BrandTitle,BrandCode FROM `brands` ");
        $d = $query->result();
         $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->BrandCode.'-'.$value->BrandTitle]=$value->BrandTitle;
            }
        }
        else{
            $array[0]='No Brand Present';
        }
        return $array;
    }


 function editSubmit($ProductId)
 {
        ///BUSINESS PROCESS 
        //1 VENDOR WILL RESGISER HIMSELF AND GET EMAIL CONFIRMATION FOR HIS LOGIN INFORMATION 
        
        $vendor_name            =       $this->input->post('vendor_name');
        $vendor_id              =       $this->input->post('vendor_id');
        $product_cost           =       $this->input->post('product_cost');
        $product_price          =       $this->input->post('product_price');
        $product_special_price  =       $this->input->post('product_special_price');
        $product_name           =       $this->input->post('product_name');
        $product_url            =       $this->input->post('product_url');
        $product_description    =       $this->input->post('product_description');
       

        $p                  = $this->input->post('product_area');
        $p                  = explode('-',$p);
        $product_source_id  = $p[2];
        $product_source     = $product_area =  $p[1];

        
        $c                       =      $this->input->post('currency');
        $c                       =      explode('-',$c);
        $currency                =      $c[0];
        
        $exchange_rate = $this->currency_model->getUSDExchangeRate($currency);

        $c = $this->input->post('categories');

        $categories = $category_ids = '';
        if(count($c)>0)
        {
             $categories = $category_ids =array();
            foreach ($c as $key => $value) {
                 $category_array = explode('-', $value);
                 $category_ids[]=$category_array[0];
                 $categories[]=$category_array[1];
            }
            $category_ids = implode(',',$category_ids);
            $categories = implode('|',$categories);
        }




        $attribue_set_id       = $attribue_set =  '';


        $as                 = explode('-', $this->input->post('attribute_set'));

        if($this->input->post('attribute_set')!='')
        {
            $attribue_set_id       =       $as[0];
            $attribue_set          =       $as[1];
        }

        
        $session                =       $this->session->userdata('logged_in');
        $added_by               =       $session['user_full_name'];
        $added_by_id            =       $session['user_id'];

        //INSERT PRODUCT DATA 

        $insert_array = array(
                'VendorName'   => $vendor_name,
                'VendorId'     => $vendor_id, 
                'Title'        => $product_name,
                'Description'  => $product_description,
                'Url'          => $product_url,
                'Area'         => $product_source,
                'ProductSourceId' =>  $product_source_id,
                'ProductSource'   =>  $product_source,
                'Price'        => ($product_price/$exchange_rate),
                'Cost'         => ($product_cost/$exchange_rate),
                'SpecialPrice' => ($product_special_price/$exchange_rate),
                'Currency'     => $currency,
                'ExchangeRate' => $exchange_rate,
                'Categories'   => $categories,
                'AttributeSet' => $attribue_set,
                'InsertedDate' => date('y-m-d h:m:s'),
                'InsertedBy'   => $added_by,
                'InsertedById' => $added_by_id
            );

        $this->db->where('ProductId',$ProductId);
        $this->db->update('products',$insert_array);


     return true;


 }




 function restSubmit(
        $product_sku,
        $product_price,
        $product_name,
        $product_url,
        $product_description)
 {
        ///BUSINESS PROCESS 
        //1 VENDOR WILL RESGISER HIMSELF AND GET EMAIL CONFIRMATION FOR HIS LOGIN INFORMATION 
        
        $product_sku            =       strtoupper($product_sku);
      
        $currency       = 'USD';
        $exchange_rate  = $this->currency_model->getUSDExchangeRate('USD');

        $session                =       $this->session->userdata('logged_in');
        $added_by               =       $session['user_full_name']-'rest-api';
        $added_by_id            =       $session['user_id'];
        //$station_currency       =       $session['station_currency'];

        //INSERT PRODUCT DATA 

        $insert_array = array(
            'Sku'                   =>  $product_sku,
            'Title'                 =>  $product_name,
            'Description'           =>  $product_description,
            'Url'                   =>  $product_url,
            'Area'                  =>  'Dubai',
            'Price'                 =>  ($product_price),
            'Currency'              =>  $currency,
            'ExchangeRate'          =>  $exchange_rate,
            'InsertedDate'          =>  date('y-m-d h:m:s'),
            'InsertedBy'            =>  $added_by,
            'InsertedById'          =>  $added_by_id
            );

        $this->db->insert('products',$insert_array);
        $product_id = $this->db->insert_id();

    $this->load->model('barcode_model');
    $this->barcode_model->barcode_genrate_and_save($product_sku);



     return $product_id;


 }







 function vendor_exist($clause)
 {
    $this->db->where('VendorName',$clause);
    $this->db->from('vendors');
    if($this->db->count_all_results()>0)
    {
        $result = true ;
    }
    else
    {
        $result = false ;
    }
    return $result;
 }



 function insert_vendor($vendor_name)
 {

        $this->load->model('vendor_model');
        $vendor_code        = $this->vendor_model->getRandomString($vendor_name,6);


        $vendor_data_array = array(
            'VendorName'=>$vendor_name,
            'VendorCode'            => $vendor_code,
             'InsertedBy'            =>'auto-api'
        );
        
    $this->db->insert('vendors',$vendor_data_array);
    
 }





function insert_new_products($offset,$limit)
{
        $r = $this->db->query("select * from products_test where IsNew=1 limit $offset,$limit");


        foreach ($r->result() as $key => $value) {
                $sku =$value->Sku;
                $this->db->where('Sku',$sku);
                $x = $this->db->count_all_results('products'); 
                if($x==0)
                {
                    echo $sku." not exist";
                    echo "<br>";

                    $Sku        = $value->Sku ;
                    $Title = $value->Title ;
                    $Description = $value->Description ;
                    $AttributeSet = $value->AttributeSet ;
                    $Categories = $value->Categories ;
                    $Price = $value->Price ;
                    $Cost = $value->Cost ;
                    $SpecialPrice = $value->SpecialPrice ;
                    $ExchangeRate = $value->ExchangeRate ;
                    $Currency = $value->Currency ;

                    
                    $VendorId = $value->VendorId;
                    $VendorName = cleanText($value->VendorName);

                    if (strpos($VendorName, 'http') !== false) {
                         $VendorName = '';
                    }
                    
                    if($VendorName!='')
                    {
                        if(!$this->vendor_exist($VendorName))
                        {
                            $v = $this->insert_vendor($VendorName);
                        } 
                        
                    }
                        



                    $product_data_array= array(
                            'Sku'=>$Sku,
                            'Title'=>$Title,
                            'Description'=>$Description,
                            'AttributeSet'=>$AttributeSet,
                            'Categories'=>$Categories,
                            'Price'=>$Price,
                            'Cost'=>$Cost,
                            'SpecialPrice'=>$SpecialPrice,
                            'ExchangeRate'=>$ExchangeRate,
                            'Currency'=>$Currency,
                             'InsertedBy'            =>'auto-api'
                        );


                    $this->db->insert('products',$product_data_array);

                    $this->load->model('barcode_model');
                    $this->barcode_model->barcode_genrate_and_save($Sku);


                     $this->db->update('products_test',array('IsNew'=>'0'));
                }
                else
                {
                }
        }

}





}
?>