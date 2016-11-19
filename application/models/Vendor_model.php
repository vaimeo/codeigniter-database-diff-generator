<?php
Class vendor_model extends CI_Model
{
 function checkUserInfoExist($clause,$user_name)
 {
   


    $this->db->like($clause,$user_name);
    $this->db->from('vendor_agents');
   return  $this->db->count_all_results();

   
 }


 function checkVendorInfoExist($clause)
 {
    $this->db->where($clause);
    $this->db->from('vendors');
    return  $this->db->count_all_results();
 }



   
 function checkVendorDuplicateName($clause)
 {
    $this->db->where($clause);
    $result = $this->db->get('vendors');
    $num_rows = $result->num_rows();
    return  array($num_rows,$result->result_array());
 }



   
 

 function userSubmit($vendor_id)
 {
        ///BUSINESS PROCESS 
        //1 VENDOR WILL RESGISER HIMSELF AND GET EMAIL CONFIRMATION FOR HIS LOGIN INFORMATION 


        $agent_full_name    = $this->input->post('agent_full_name');
        $agent_designation  = $this->input->post('agent_designation');
        $agent_phone        = $this->input->post('agent_phone');
        $agent_email        = $this->input->post('agent_username');
        $agent_whatsapp     = $this->input->post('agent_whatsapp');
        $agent_wechat       = $this->input->post('agent_wechat');
        $agent_qq           = $this->input->post('agent_qq');


//INSERT VENDOR AGENT DATA
        $vendor_agent_insert_array = array(
            'AgentUserName'         =>  $agent_email,
            'AgentFullName'         =>  $agent_full_name,
            'AgentDesignation'      =>  $agent_designation,
            'AgentPhone'            =>  $agent_phone,
            'AgentEmail'            =>  $agent_email,
             'WhatsAppId'           =>  $agent_whatsapp,
             'WeChatId'             =>  $agent_wechat,
             'VendorId'             =>  $vendor_id,
             'QqId'                 =>  $agent_qq,
             'Status'               =>  'inactive'
            );

     $this->db->insert('vendor_agents',$vendor_agent_insert_array);
    $vendor_agent_id = $this->db->insert_id();


        $this->db->where('VendorAgentId',$vendor_agent_id);
        $this->db->select('*');
        return $this->db->get('vendor_agents')->row(); 

     return true;


 }


 function userEditSubmit()
 {
        
        $vendor_agent_id    = $this->input->post('vendor_agent_id');
        $agent_full_name    = $this->input->post('agent_full_name');
        $agent_designation  = $this->input->post('agent_designation');
        $agent_phone        = $this->input->post('agent_phone');
        $agent_email        = $this->input->post('agent_username');
        $agent_whatsapp     = $this->input->post('agent_whatsapp');
        $agent_wechat       = $this->input->post('agent_wechat');
        $agent_qq           = $this->input->post('agent_qq');


//INSERT VENDOR AGENT DATA
        $vendor_agent_insert_array = array(
            'AgentUserName'         =>  $agent_email,
            'AgentFullName'         =>  $agent_full_name,
            'AgentDesignation'      =>  $agent_designation,
            'AgentPhone'            =>  $agent_phone,
            'AgentEmail'            =>  $agent_email,
             'WhatsAppId'           =>  $agent_whatsapp,
             'WeChatId'             =>  $agent_wechat,
             'QqId'                 =>  $agent_qq,
             'Status'               =>  'inactive'
            );

        /*get the old name of vendor maneger from vendors*/
        $this->db->where('VendorAgentId',$vendor_agent_id);
        $result = $this->db->get('vendor_agents')->row();

            //updating vendor
            $this->db->where('VendorAdminId',$vendor_agent_id);
            $this->db->update('vendors',array('VendorAdmin'=>$agent_full_name));

            //updating agents
        $this->db->where('VendorAgentId',$vendor_agent_id);
        $this->db->update('vendor_agents',$vendor_agent_insert_array);
  /*get the old name of vendor maneger from vendors*/
        $this->db->where('VendorAgentId',$vendor_agent_id);
 return  $this->db->get('vendor_agents')->row();


     return true;


 }








 function bankSubmit($vendor_id)
 {
        ///BUSINESS PROCESS 
        //1 VENDOR WILL RESGISER HIMSELF AND GET EMAIL CONFIRMATION FOR HIS LOGIN INFORMATION 
        //VENDOR CODE
        //USERNAME
        //PASSWORD


        $primary_bank_account_title = $this->input->post('primary_bank_account_title');
        $primary_bank_account_number= $this->input->post('primary_bank_account_number');
        $primary_bank_name          = $this->input->post('primary_bank_name');
        $primary_bank_swift_code    = $this->input->post('primary_bank_swift_code');
        $primary_bank_iban_number   = $this->input->post('primary_bank_iban_number');
        $primary_bank_country       = $this->input->post('primary_bank_country');
        $primary_bank_city          = $this->input->post('primary_bank_city');
        $primary_bank_area          = $this->input->post('primary_bank_area');
        $primary_bank_address       = $this->input->post('primary_bank_address');
        $Currency       = $this->input->post('Currency');
   


        $this->db->where(" RecordFor='vendor' and RecordForId='$vendor_id' ");
        $result = $this->db->get('bank_accounts');
        $num_rows = $result->num_rows();


//INSERT VENDOR BANK DATA
        $vendor_bank_insert_array= array(
            'BankAccountTitle'      =>$primary_bank_account_title,
            'BankAccountNumber'     =>$primary_bank_account_number,
            'BankName'              =>$primary_bank_name,
            'BankSwiftCode'         =>$primary_bank_swift_code,
            'BankIbanNumber'        =>$primary_bank_iban_number,
            'BankCountry'           =>$primary_bank_country,
            'BankCity'              =>$primary_bank_city,
            'BankArea'              =>$primary_bank_area,
            'BankAddress'           =>$primary_bank_address,
            'RecordFor'             =>'vendor',
            'RecordForId'           => $vendor_id,
            'IsPrimary'             =>($num_rows==0?'1':'0'),
            'Currency'   =>$Currency
            );

        $this->db->insert('bank_accounts',$vendor_bank_insert_array);
      
      $bank_account_id = $this->db->insert_id();


        $this->db->where('BankAccountId',$bank_account_id);
        $this->db->select('*');
        return $this->db->get('bank_accounts')->row(); 

     return true;


 }




 function bankEditSubmit()
 {
        ///BUSINESS PROCESS 
        //1 VENDOR WILL RESGISER HIMSELF AND GET EMAIL CONFIRMATION FOR HIS LOGIN INFORMATION 
        //VENDOR CODE
        //USERNAME
        //PASSWORD


        $bank_account_id            = $this->input->post('BankAccountId');
        $primary_bank_account_title = $this->input->post('primary_bank_account_title');
        $primary_bank_account_number= $this->input->post('primary_bank_account_number');
        $primary_bank_name          = $this->input->post('primary_bank_name');
        $primary_bank_swift_code    = $this->input->post('primary_bank_swift_code');
        $primary_bank_iban_number   = $this->input->post('primary_bank_iban_number');
        $primary_bank_country       = $this->input->post('primary_bank_country');
        $primary_bank_city          = $this->input->post('primary_bank_city');
        $primary_bank_area          = $this->input->post('primary_bank_area');
        $primary_bank_address       = $this->input->post('primary_bank_address');
        $Currency       = $this->input->post('Currency');
   


//INSERT VENDOR BANK DATA
        $vendor_bank_insert_array= array(
            'BankAccountTitle'      =>$primary_bank_account_title,
            'BankAccountNumber'     =>$primary_bank_account_number,
            'BankName'              =>$primary_bank_name,
            'BankSwiftCode'         =>$primary_bank_swift_code,
            'BankIbanNumber'        =>$primary_bank_iban_number,
            'BankCountry'           =>$primary_bank_country,
            'BankCity'              =>$primary_bank_city,
            'BankArea'              =>$primary_bank_area,
            'BankAddress'           =>$primary_bank_address,
            'Currency'   =>$Currency
            );


        $this->db->where('BankAccountId',$bank_account_id);
        $this->db->update('bank_accounts',$vendor_bank_insert_array);

        $this->db->where('BankAccountId',$bank_account_id);
        $this->db->select('*');
        return $this->db->get('bank_accounts')->row(); 

     return true;


 }


 
 



 function editSubmit($vendor_id)
 {



        $vendor_name        = $this->input->post('vendor_name');
        $vendor_country     = $this->input->post('vendor_country');
        $vendor_city        = $this->input->post('vendor_city');
        $vendor_postal_code = $this->input->post('vendor_postal_code');
        $vendor_address     = $this->input->post('vendor_address');
        $vendor_phone       = $this->input->post('vendor_phone');
        $vendor_email       = $this->input->post('vendor_email');
        $vendor_web         = $this->input->post('vendor_web');
        $description        = $this->input->post('description');
/*
        $credit_days        = $this->input->post('credit_days');
        $tax_vat_no         = $this->input->post('tax_vat_no');*/

        
        $this->load->model('user_model');
        $system_admin_id    = $this->input->post('system_admin_id');
        $system_admin_data  = $this->user_model->getUserInfoById($system_admin_id);
        $system_admin_name  = $system_admin_data->FullName;
 

        $session                =       $this->session->userdata('logged_in');
        $added_by               =       $session['user_full_name'];
        $added_by_id            =       $session['user_id'];


//INSERT VENDOR DATA 

        $vendor_insert_array = array(
            'VendorName'            =>$vendor_name,
            'VendorCountry'         =>$vendor_country,
            'VendorCity'            =>$vendor_city,
            'VendorPostalCode'      =>$vendor_postal_code,
            'VendorAddress'         =>$vendor_address,
            'VendorPhone'           =>$vendor_phone,
            'VendorEmail'           =>$vendor_email,
            'VendorWeb'             =>$vendor_web,/*
            'CreditDays'            =>$credit_days,
            'TaxVatNo'              =>$tax_vat_no,*/
            'SystemAdminId'         =>$system_admin_id,
            'SystemAdmin'           =>$system_admin_name,
            'Description'           =>$description
            );

        $this->db->where('VendorId',$vendor_id);
        $this->db->update('vendors',$vendor_insert_array);


     return true;


 }



 function registraionSubmit()
 {


        $vendor_name        = $this->input->post('vendor_name');
        $vendor_country     = $this->input->post('vendor_country');
        $vendor_city        = $this->input->post('vendor_city');
        $vendor_postal_code = $this->input->post('vendor_postal_code');
        $vendor_address     = $this->input->post('vendor_address');
        $vendor_phone       = $this->input->post('vendor_phone');
        $vendor_email       = $this->input->post('vendor_email');
        $vendor_web         = $this->input->post('vendor_web');
        $description        = $this->input->post('description');
      
        $this->load->model('user_model');
        $system_admin_id    = $this->input->post('system_admin_id');
        $system_admin_data  = $this->user_model->getUserInfoById($system_admin_id);
        $system_admin_name  = $system_admin_data->FullName;

        $transaction_currency       = $this->input->post('transaction_currency');
        $credit_days        = $this->input->post('credit_days');
        $tax_vat_no         = $this->input->post('tax_vat_no');
        $vendor_code        = $this->getRandomString($vendor_name,6);


        $agent_full_name    = $this->input->post('agent_full_name');
        $agent_designation  = $this->input->post('agent_designation');
        $agent_phone        = $this->input->post('agent_phone');
        $agent_email        = $this->input->post('agent_username');
        $agent_whatsapp     = $this->input->post('agent_whatsapp');
        $agent_wechat       = $this->input->post('agent_wechat');
        $agent_qq           = $this->input->post('agent_qq');


        $session                =       $this->session->userdata('logged_in');
        $added_by               =       $session['user_full_name'];
        $added_by_id            =       $session['user_id'];


//INSERT VENDOR DATA 

        $vendor_insert_array = array(
            'VendorName'            =>$vendor_name,
            'VendorCode'            => $vendor_code, 
            'VendorCountry'         =>$vendor_country,
            'VendorCity'            =>$vendor_city,
            'VendorPostalCode'      =>$vendor_postal_code,
            'VendorAddress'         =>$vendor_address,
            'VendorPhone'           =>$vendor_phone,
            'VendorEmail'           =>$vendor_email,
            'VendorWeb'             =>$vendor_web,
            'TransactionCurrency'   =>$transaction_currency,
            'CreditDays'            =>$credit_days,
            'TaxVatNo'              =>$tax_vat_no,
            'Status'=>'pending_acount_approval',
            'Description'           =>$description,
            'SystemAdminId'         =>$system_admin_id,
            'SystemAdmin'           =>$system_admin_name,
            'InsertedBy'            =>$added_by,
            'InsertedById'          =>$added_by_id,
            'InsertedDate'          =>date('Y-m-d h:m:s')            
            );

        $this->db->insert('vendors',$vendor_insert_array);
        $vendor_id = $this->db->insert_id();



//INSERT VENDOR AGENT DATA
        $vendor_agent_insert_array = array(
            'AgentUserName'         =>  $agent_email,
            'AgentFullName'         =>  $agent_full_name,
            'AgentDesignation'      =>  $agent_designation,
            'AgentPhone'            =>  $agent_phone,
            'AgentEmail'            =>  $agent_email,
             'WhatsAppId'           =>  $agent_whatsapp,
             'WeChatId'             =>  $agent_wechat,
             'VendorId'             =>  $vendor_id,
             'QqId'                 =>  $agent_qq,
             'Status'               =>  'inactive'
            );

     $this->db->insert('vendor_agents',$vendor_agent_insert_array);
      $vendor_agent_id = $this->db->insert_id();


            $updated_array=array(
            'VendorAdminId'         =>$vendor_agent_id,
            'VendorAdmin'           =>$agent_full_name
            );


        $this->db->where('VendorId',$vendor_id);
        $this->db->update('vendors',$updated_array);



     return true;


 }


function getRandomString($validCharacters,$length) {

/*
$validCharNumber = strlen($validCharacters);
$result ="";
for ($i = 0; $i < $length; $i++) {
$index = mt_rand(0, $validCharNumber - 1);
$result .= $validCharacters[$index];
}*/

$result = substr(preg_replace('/\s+/', '',strtoupper($validCharacters)),0,3).rand(000,999);

return $result;

}


 function getVendorInfoById($VendorId)
 {
        $this->db->where('VendorId',$VendorId);
        $this->db->select('*');
        return $this->db->get('vendors')->row(); 
 }


 function getVendorAgentsByVendorId($VendorId)
 {
        $this->db->where('VendorId',$VendorId);
        $this->db->select('*');
        return $this->db->get('vendor_agents'); 
 }

  function getVendorBanks($VendorId)
 {
        $this->db->where('RecordFor','vendor');
        $this->db->where('RecordForId',$VendorId);
        $this->db->select('*');
        return $this->db->get('bank_accounts'); 
 }
 
 

function getVendorInfoJson($q,$ClauseField)
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

    $query = $this->db->query("select VendorId,VendorName,VendorCode,VendorAddress from vendors $Cond");
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


    function getVendorIdJson($q,$ClauseField)
{
    $ClauseField=explode(',',$ClauseField);

    $Cond="";
    foreach ($ClauseField as $ClauseFieldkey => $ClauseFieldvalue) {
        if($ClauseFieldkey==0){
              $Cond = " where $ClauseFieldvalue = '$q'";
          }else{
            $Cond.=" OR  $ClauseFieldvalue = '$q'";
          }
    }

    $query = $this->db->query("select VendorId,VendorName,VendorCode,VendorAddress from vendors $Cond");
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








function getVendorsJsonBySearch($q)
{
    $Cond=" Where VendorCode Like '%$q%' or  VendorName Like '%$q%'  or  Brands Like '%$q%'";


    $query = $this->db->query("select VendorId,VendorName,VendorCode,VendorAddress from vendors $Cond");
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


function addSubmitBrands(){

    $brands  = $this->input->post('brands');
    $vendor_id  = $this->input->post('vendor_id');

    $brandId=$brandTitle = array();

    foreach ($brands as $key => $value) {
        $v= explode('-',$value);
        $brandId[]=$v[0];
        $brandTitle[]=$v[1];
    }

    $brandIds=json_encode($brandId);
    $brandTitles=json_encode($brandTitle);


    $this->db->where('VendorId',$vendor_id);
    $this->db->update('vendors',array('BrandIds' =>$brandIds,'Brands'=>$brandTitles ));


    
}


function formatVendorBrandsForDd($id_array,$title_array)
{
    $idArray = json_decode($id_array);
    $titleArray = json_decode($title_array);
    
    return (count($idArray)>0?array_combine($idArray, $titleArray):array());
}

}
?>