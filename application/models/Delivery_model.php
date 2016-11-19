<?php

class delivery_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    function validateShipping($OrderId){   
    /**
        IN THIS FUNCTON WE CAN CHECK EITHER WE CAN SHIP ORDER OR NO IF ALL ITEMS ARE SENT TO CUSTOMER THEN WE CANNOT SHIP ANYMORE PRODUCT EITHER OR WE ADD ANY NEW ITEM TO EXISTING ORDER
    **/

        return true;
    }



    function generateAirwayBillNumber(){
        $awb = 'K'.date('ymdHis');
        return $awb;
    }


    function getDeliveryTypeList(){
        $query = $this->db->query("select * from delivery_types");
        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->DeliveryTypeId]=$value->DeliveryType;
            }
        }
        else{
            $array[0]='No Deleivery Type Present';
        }
        return $array;
    }





    function deliveryHistory(){

    }


    function getDeliveryTypeById($DeliveryTypeId){
         $query = $this->db->query("select DeliveryType from delivery_types where DeliveryTypeId='$DeliveryTypeId'");
        return $query->row()->DeliveryType;
      
    }



    function getDeliveryHistory($OrderId){
        $this->db->where('OrderId',$OrderId);
        $d  =   $this->db->get('deliveries');
        $data_array=array();
        if($d->num_rows()>0){
            foreach ($d->result() as $key => $value) {
                $this->db->where('DeliveryId',$value->DeliveryId);
                $i  =   $this->db->get('deliveries_items');
                $value->DeliveryDateFormated = date("D d M Y h:m:s", strtotime($value->DeliveryDate));
                $data_array[]=array('delivery'=>$value,'item'=>$i->result());
            }
        }
        return $data_array;
    }




    function getCourierConfigByOrder($PaymentMethod,$CountryCode,$CustomerCurrency)
    {
      $PaymentMethod = strtolower($PaymentMethod);
     

        $query = $this->db->query("
            SELECT
                `c`.`CourierId`    AS CourierId,
                `c`.`CourierTitle`    AS CourierTitle,
                `dc`.`ApiActive`    AS ApiActive,
                `dc`.`UseLocalAwb`    AS UseLocalAwb

            FROM `delivery_config` dc
             INNER JOIN
                couriers c 
                    ON c.CourierId=dc.CourierId
                AND
                     dc.PaymentMode='$PaymentMethod'
                AND 
                     dc.CountryCode='$CountryCode'
                AND 
                     dc.Currency='$CustomerCurrency'                     
             ");

        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array['value='.$value->CourierId.' is-online='.$value->ApiActive.' use_local_awb='.$value->UseLocalAwb]=$value->CourierTitle;
            }
        }
        else{
            $array[0]='No Courier Supported for '.$CountryCode.' and '.$PaymentMethod;
        }
        return $array;
    }


    function teamExpressBooking()
    {


        $this->load->library("Nusoap");

            $proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
            $proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
            $proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
            $proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
            $client = new nusoap_client('http://teamexpressme.com/api/webservice.php?wsdl', 'wsdl',
                                    $proxyhost, $proxyport, $proxyusername, $proxypassword);


            $err = $client->getError();
            if ($err) {
            }

            $data=array('status'=>0,'AirwayBillNumber'=>'','delivery_id'=>0);

            $consignee_address_line1=$_POST['consignee_address_line1'];

            $desc_of_goods  = substr($_POST['pack_description'], 0,200);
            $special_instructions  = substr($_POST['special_instructions'], 0,200);



            $param[] = array(
                "ToCompany"         =>      $_POST['consignee_company_name'],
                "ToAddress"         =>      $consignee_address_line1,
                "ToLocation"        =>      $_POST['consignee_city'],
                "ToCountry"         =>      $_POST['consignee_country_name'],
                "ToCperson"         =>      $_POST['consignee_person_name'],
                "ToContactno"       =>      $_POST['consignee_phone'],
                "ReferenceNumber"   =>      $_POST['pack_reference'],
                "CompanyCode"       =>      "10001",
                "Weight"            =>      $_POST['pack_weight'],
                "Pieces"            =>      $_POST['pack_pieces'],
                "PackageType"       =>      "Document",
                "AwbNumber"         =>      $_POST['awb_number'],
                "NcndAmount"        =>      $_POST['cod_amount'],
                "ItemDescription"   =>      $desc_of_goods,
                "SpecialInstruction"=>      $special_instructions
                );



            $result = $client->call('Booking', $param, '', '', false, true);
            $status = 0;
            $awb_no = $result;
            $file_name=$message='';



            // Check for a fault
            if ($client->fault) {
                        $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$result.'.
                          </div>';

                        //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);                          
            } else {
                $err = $client->getError();
                if ($err) {
                    $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$err.'.
                          </div>';

                        //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);                          
                } else {
                    $awb_no = $result;
                    if($awb_no==$_POST['awb_number'])
                    {




                        $pram_awb[] = array("BookingNumber" =>$awb_no,"CompanyCode"=>"10001"); 
                        $result = $client->call('GetAirwayBill', $pram_awb, '', '', false, true);
                        $file_name='TEAMEX-AWB-'.$awb_no.'.pdf';
                        $data = base64_decode($result[0]);
                        file_put_contents('uploads/awb/pdf/'.$file_name,$data);
                        $this->insert_delivery($awb_no,$file_name);

                        $message='<div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Success!</h4>Booking is Done.
                          </div>';
                        $status = 1;


                        ///UPDATE JOB EXECUTION AS DONE 
                        $this->general_model->updateExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);

                    }
                    else
                    {
                                $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$result.'.
                          </div>';

                        //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);


                    }
                    



                }

                    $data=array('status'=>$status,'awb_no'=>$awb_no,'file'=>'uploads/awb/pdf/'.$file_name,'delivery_id'=>0,'message'=>$message);

                    return $data;
            }

                
    }




    function parzelBooking()
    {


        

            $data=array('status'=>0,'AirwayBillNumber'=>'','delivery_id'=>0);

            $consignee_address_line1=$_POST['consignee_address_line1'];

            $desc_of_goods  = substr($_POST['pack_description'], 0,200);
            $special_instructions  = substr($_POST['special_instructions'], 0,200);



            $curl = curl_init();

            curl_setopt_array($curl, array(

                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'http://parzel.com/vendor/T0214.php',
                    CURLOPT_USERAGENT => 'PARZEL EXPRESS',
                    CURLOPT_POST => 1,
                    CURLOPT_FOLLOWLOCATION =>1,
                    CURLOPT_POSTFIELDS => array(

                    'orderid' => $_POST['pack_reference'],//order id 
                    'consignee' => $_POST['consignee_person_name'],//first name
                    'consigneecperson' => $_POST['consignee_person_name'],//;last name
                    'consigneeaddress1' => $consignee_address_line1,
                    'consigneeaddress2' => '',
                    'consigneephone' => $_POST['consignee_phone'],
                    'consigneecity' => $_POST['consignee_city'],
                    'consigneecountry' => $_POST['consignee_country_name'],
                    'price' => $_POST['cod_amount'],
                    'weight' => $_POST['pack_weight'],
                    'quantity' => $_POST['pack_pieces'],
                    'paymentmethod' => 'COD', //Need Payment Method Code Ex: 'COD', 'CC';
                    'goodsdescription' => $desc_of_goods,
                    'specialinstruction' => $special_instructions


                )

            ));

            $result = curl_exec($curl);
           // echo $result;
            $curl_error = '';
            if(!$result){
                $curl_error = 'Error:"' . curl_error($curl) . '" - Code ' . curl_errono($curl);
                }
            curl_close($curl); 

            // $result = $client->call('Booking', $param, '', '', false, true);
             $status = 0;
            
             $file_name=$message='';

             // if error
             if($curl_error)
             {
                  $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$curl_error.'.
                           </div>';

                                    //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);


             }else{//no error
                 // currently $result is "Your AWBNo is: 9470008471"
                     $result = explode(':', $result);
                     $result = trim($result[1]);
                     $awb_no = $result;

                //TODO: need to change this file
                     $file_name='TEMPAWB.pdf';
                     $this->insert_delivery($awb_no,$file_name);

                        $message='<div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Success!</h4>Booking is Done.
                        </div>';
                        $status = 1;
                        ///UPDATE JOB EXECUTION AS DONE 
                        $this->general_model->updateExecutionState('add','online_booking_submit',uri_string());

     
              

             }

                   $data=array('status'=>$status,'awb_no'=>$awb_no,'file'=>'uploads/awb/pdf/'.$file_name,'delivery_id'=>0,'message'=>$message);


                   return $data;

    }

    
    function dhlBooking()
    {
        $shipper_adderss_line1=$_POST['shipper_adderss_line1'];//question

        $shipper_address_line='<AddressLine>'.substr($_POST['shipper_adderss_line1'], 0,35).'</AddressLine>';

        if(strlen($shipper_adderss_line1)>35){
        $shipper_address_line.='<AddressLine>'.substr($_POST['shipper_adderss_line1'],36,35).'</AddressLine>';
        }
          



        $consignee_address_line1=$_POST['consignee_address_line1'];

        $consignee_address_line='<AddressLine>'.substr($_POST['consignee_address_line1'], 0,35).'</AddressLine>';

        if(strlen($consignee_address_line1)>35){
        $consignee_address_line.='<AddressLine>'.substr($_POST['consignee_address_line1'],36,35).'</AddressLine>';
        }


        // prepare string
        $str = '<?xml version="1.0" encoding="UTF-8"?>
            <req:ShipmentRequest xmlns:req="http://www.dhl.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com ShipmentRequest.xsd" schemaVersion="1.0">
             <Request>
              <ServiceHeader>
               <MessageTime>'. date('Y-m-d').'T'.date('H:m:s').'-05:50</MessageTime>
               <MessageReference>'.substr('REQ-0123456789123456789'.rand(0,100084385),0,31).'</MessageReference>
               <SiteID>Kingsouq</SiteID>
               <Password>ItQgFVwT7N</Password>
              </ServiceHeader>
             </Request>
             <RegionCode>AM</RegionCode>
             <RequestedPickupTime>Y</RequestedPickupTime>
             <NewShipper>Y</NewShipper>
             <LanguageCode>en</LanguageCode>
             <PiecesEnabled>Y</PiecesEnabled>
             <Billing>
              <ShipperAccountNumber>958038515</ShipperAccountNumber>
              <ShippingPaymentType>S</ShippingPaymentType>
              <BillingAccountNumber>958038515</BillingAccountNumber>
              <DutyPaymentType>S</DutyPaymentType>
              <DutyAccountNumber>958038515</DutyAccountNumber>
             </Billing>
             <Consignee>
              <CompanyName>'.$_POST['consignee_company_name'].'</CompanyName>
              '.$consignee_address_line.'
              <City>'.$_POST['consignee_city'].'</City>
              <PostalCode>'.$_POST['consignee_postal_code'].'</PostalCode>
              <CountryCode>'.$_POST['consignee_country'].'</CountryCode>
              <CountryName>'.$_POST['consignee_country_name'].'</CountryName>
              <Contact>
               <PersonName>'.$_POST['consignee_person_name'].'</PersonName>
               <PhoneNumber>'.$_POST['consignee_phone'].'</PhoneNumber>
               <Email>'.$_POST['consignee_email'].'</Email>
              </Contact>
             </Consignee>
             <Dutiable>
              <DeclaredValue>'.$_POST['pack_decalred_value'].'</DeclaredValue>
              <DeclaredCurrency>USD</DeclaredCurrency>
             </Dutiable>
             <Reference>
              <ReferenceID>'.$_POST['pack_reference'].'</ReferenceID>
             </Reference>
             <ShipmentDetails>
              <NumberOfPieces>'.$_POST['pack_pieces'].'</NumberOfPieces>
              <Pieces>
               <Piece>
                <PieceID>1</PieceID>
                <PackageType>EE</PackageType>
                <Weight>'.$_POST['pack_weight'].'</Weight>
                <Width>'.$_POST['pack_weidth'].'</Width>
                <Height>'.$_POST['pack_height'].'</Height>
                <Depth>'.$_POST['pack_len'].'</Depth>
               </Piece>
              </Pieces>
              <Weight>'.$_POST['pack_weight'].'</Weight>
              <WeightUnit>L</WeightUnit>
              <GlobalProductCode>P</GlobalProductCode>
              <LocalProductCode>P</LocalProductCode>
              <Date>'.$_POST['pack_shipment_date'].'</Date>
              <Contents>'.substr($_POST['pack_description'], 0,90).'</Contents>
              <DoorTo>DD</DoorTo>
              <DimensionUnit>I</DimensionUnit>
              <InsuredAmount>'.$_POST['pack_insure_value'].'</InsuredAmount>
              <PackageType>EE</PackageType>
              <IsDutiable>Y</IsDutiable>
              <CurrencyCode>USD</CurrencyCode>
             </ShipmentDetails>
             <Shipper>
              <ShipperID>751008818</ShipperID>
              <CompanyName>'.$_POST['shipper_company_name'].'</CompanyName>
              <RegisteredAccount>751008818</RegisteredAccount>
              '.$shipper_address_line.'
              <City>'.$_POST['shipper_city'].'</City>
              <CountryCode>'.$_POST['shipper_country_code'].'</CountryCode>
              <CountryName>'.$_POST['shipper_country_name'].'</CountryName>
              <Contact>
               <PersonName>'.$_POST['shipper_contact_name'].'</PersonName>
               <PhoneNumber>'.$_POST['shipper_phone_number'].'</PhoneNumber>
               <PhoneExtension>3403</PhoneExtension>
               <Email>'.$_POST['shipper_email_adderss'].'</Email>
              </Contact>
             </Shipper>
             <EProcShip>N</EProcShip>
             <LabelImageFormat>PDF</LabelImageFormat>
             <Label>
                <LabelTemplate>8X4_A4_TC_PDF</LabelTemplate>
                <ReceiptTemplate>SHIP_RECPT_A4_RU_PDF</ReceiptTemplate> 
              </Label>
            </req:ShipmentRequest>';

            $environment='test';

            // call call function
           // $d =  $this->call($str); 
            // function here
             if (!$ch = curl_init())
                {
                    throw new \Exception('could not initialize curl');
                }
                $environment ='test';
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                if($environment=='test'){
                curl_setopt($ch, CURLOPT_URL, 'https://xmlpitest-ea.dhl.com/XMLShippingServlet');
                }
                else
                {
                curl_setopt($ch, CURLOPT_URL, 'https://xmlpi-ea.dhl.com/XMLShippingServlet');
                  
                }
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_PORT , 443);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
                $result = curl_exec($ch);
                
                if (curl_error($ch))//error
                {
                    //return false;
                    $curl_error = "Could not process!";
                $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$curl_error.'.
                           </div>';
                               $status = 0;
                           $data=array('status'=>$status,'message' => $message);

                                    //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);

                }
                else
                {
                    curl_close($ch);
                    $xml = new SimpleXMLElement($result);
                    $file_name='';
                    $datastatus=array('status'=>0,'AirwayBillNumber'=>'','delivery_id'=>0);
                   
                                          


                    if($xml->AirwayBillNumber)
                     {
                     $d=$xml->AirwayBillNumber;
                      $data = base64_decode($xml->LabelImage->OutputImage);
                     $awb_no=$d;
                      $file_name='DHL-AWB-'.$awb_no.'.pdf';
                       // $data = base64_decode($result[0]);
                        file_put_contents('uploads/awb/pdf/'.$file_name,$data);
                      //$file_name=(string)$d.'_DHL.pdf';
                       // file_put_contents('uploads/awb/pdf/'.$file_name,$data);
                        
                        
                        $this->insert_delivery($awb_no,$file_name);
                         $message='<div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h4><i class="icon fa fa-warning"></i> Success!</h4>Booking is Done.
                                </div>';
                                $status = 1;
                                ///UPDATE JOB EXECUTION AS DONE 
                                $this->general_model->updateExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);

                                $data=array('status'=>$status,'awb_no'=>$awb_no,'file'=>'uploads/awb/pdf/'.$file_name,'delivery_id'=>0,'message'=>$message);

                           

                }else{
                     $curl_error = $result."AWB not returned!";
                $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$curl_error.'.
                           </div>';
                            $status = 0;
                           $data=array('status'=>$status,'message' => $message);

                                    //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);


                }
                }
            // end of function

            
            return $data;
       
        //}
    }

    public function call($request)
    {
        
        if (!$ch = curl_init())
        {
            throw new \Exception('could not initialize curl');
        }
        $environment ='test';
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($environment=='test'){
        curl_setopt($ch, CURLOPT_URL, 'https://xmlpitest-ea.dhl.com/XMLShippingServlet');
        }
        else
        {
        curl_setopt($ch, CURLOPT_URL, 'https://xmlpi-ea.dhl.com/XMLShippingServlet');
          
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_PORT , 443);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $result = curl_exec($ch);
        
        if (curl_error($ch))
        {
            return false;
        }
        else
        {
            curl_close($ch);
        }

        return $result;
    }




    function smsaExpressBooking($request_key)
    {



        $this->load->library("Nusoap");

            $proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
            $proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
            $proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
            $proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
            $client = new nusoap_client('http://track.smsaexpress.com/SeCom/SMSAwebService.asmx?WSDL', 'wsdl',
                                    $proxyhost, $proxyport, $proxyusername, $proxypassword);


            $err = $client->getError();
            if ($err) {
            }

            $data=array('status'=>0,'AirwayBillNumber'=>'','delivery_id'=>0);

            $consignee_address_line1=$_POST['consignee_address_line1'];

            $desc_of_goods  = substr($_POST['pack_description'], 0,200);
            $special_instructions  = substr($_POST['special_instructions'], 0,200);

                
                $param = array();

                      $param['passKey']  =      'Testing0' ;
                      $param['refNo']    =      $_POST['pack_reference'];
                      $param['sentDate'] =      date('Y-m-d');
                      $param['idNo']     =      rand(0,100000);
                      $param['cName']    =      $_POST['consignee_person_name'];
                      $param['cntry']    =      'KSA';
                      $param['cCity']    =      $_POST['consignee_city'];
                      $param['cZip']     =      $_POST['consignee_postal_code'];
                      $param['cPOBox']   =      $_POST['consignee_postal_code'];
                      $param['cMobile']  =      $_POST['consignee_phone'];
                      $param['cTel1']    =      $_POST['consignee_phone'];
                      $param['cTel2']    =      $_POST['consignee_phone'];
                      $param['cAddr1']   =      substr($_POST['consignee_address_line1'], 0,35);
                      $param['cAddr2']   =      substr($_POST['consignee_address_line1'],36,35);
                      $param['shipType'] =      'DLV';
                      $param['PCs']      =      $_POST['pack_pieces'];
                      $param['cEmail']   =      $_POST['consignee_email'];
                      $param['carrValue']=      $_POST['carraige_value'];
                      $param['carrCurr'] =      'SAR';
                      $param['codAmt']   =      $_POST['cod_amount'];
                      $param['weight']   =      $_POST['pack_weight'];
                      $param['custVal']  =      $_POST['customs_value'];
                      $param['custCurr'] =      'SAR';
                      $param['insrAmt']  =      $_POST['insurance_value'];
                      $param['insrCurr'] =      'SAR';
                      $param['itemDesc'] =      substr($_POST['pack_description'], 0,50);
                      $param['sName']    =      $_POST['shipper_company_name'];
                      $param['sContact'] =      $_POST['shipper_contact_name'];
                      $param['sAddr1']   =      substr($_POST['shipper_adderss_line1'], 0,35);
                      $param['sAddr2']   =      substr($_POST['shipper_adderss_line1'], 0,35);
                      $param['sCity']    =      $_POST['shipper_city'];
                      $param['sPhone']   =      $_POST['shipper_phone_number'];
                      $param['sCntry']   =      $_POST['shipper_country_name'];
                      $param['prefDelvDate']  = '0';
                      $param['gpsPoints']     = '0';



            $result = $client->call('addShip', $param, '', '', false, true);
            $status = 0;
            $awb_no = $result;
            $file_name=$message='';



            // Check for a fault
            if ($client->fault) {
                        $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$result.'.
                          </div>';
            } else {
                $err = $client->getError();
                if ($err) {
                    $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$err.'.
                          </div>';
                } else {
                    $awb_no = $result;

                    $awb_no=$result['addShipResult'];



                    if (strpos($awb_no,'Failed') !== false) {
                        

                        $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$awb_no.'.
                          </div>';
                        $status=0;

                        //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);


                    }else
                    {


                        $pram_awb[] = array('awbNo'=>$awb_no,'passkey'=>'Testing0'); 
                        $result = $client->call('getPDF', $pram_awb, '', '', false, true);
                        $file_name='SMSA-AWB-'.$awb_no.'.pdf';
                        $data = base64_decode($result['getPDFResult']);
                        file_put_contents('uploads/awb/pdf/'.$file_name,$data);
                        $this->insert_delivery($awb_no,$file_name);

                        $message='<div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Success!</h4>Booking is Done.
                          </div>';
                        $status = 1;


                        ///UPDATE JOB EXECUTION AS DONE 
                        $this->general_model->updateExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);

                    }

                }

                    $data=array('status'=>$status,'awb_no'=>$awb_no,'file'=>'uploads/awb/pdf/'.$file_name,'delivery_id'=>0,'message'=>$message);

                    return $data;
            }

                
    }


  function fodelBooking($request_key,$shopBooking=false)
  {
    

       $status = 0;
      $awb_no = '';
      $file_name=$message='';



    $fodel_method = 'delivery/create';

      $requestTime = time(); //timestamp of request
      $secretKey = "e668d4adcfd23ff9ba7b810e9e935ddf"; //secretKey from Fodel
      $params = array();
      $params["recipient_name"] = $_POST['consignee_person_name'];
      $params["phone"] = $_POST['consignee_phone'];
      $params["country"] = $_POST['consignee_country_name'];
      $params["city"] = $_POST['consignee_city'];
      $params["area"] = $_POST['consignee_area'];
      $params["address"] = $_POST['shipper_adderss_line1'];
      $params["weight"] = $_POST['pack_weight'];
      $params["is_cod"] = $_POST['is_cod'];
      $params["price_cod"] = $_POST['cod_amount'];
      $params["order_no"] = $_POST['pack_reference'];
      $params["expected_time"] = "12:00-14:00";
      $params["expected_date"] = "2016-10-18";
      $params["app_key"] = 2001; //appKey from Fodel
      if($shopBooking)
      {
        $params["shop_id"] = 1;
        $fodel_method = 'collectionpoint/create';
      }
      $params["ts"] = $requestTime;

      //generate the sign from the paramters with eht secretKey
      $signString = generate_sign($params,$secretKey);
      $params['sign'] = $signString;
                                               
    //$data_string = json_encode($params);                                                                                   
                                                                                                                         
    $ch = curl_init('http://api.test.fo-del.com/waybill/'.$fodel_method);
    
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                                                                                         
    $response = curl_exec($ch);
    
 
                if (curl_error($ch))//error
                {
                    //return false;
                    $curl_error = "Could not process!";
                    $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$curl_error.'.
                           </div>';
                               $status = 0;
                        //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);

                }
                else
                {

                    curl_close($ch);

                    $result = json_decode($response);

                    $file_name='';
                    $datastatus=array('status'=>0,'AirwayBillNumber'=>'','delivery_id'=>0);
                   
                                          


                    if($result->code=='1001')
                     {

                      $awb_no='fodel-'.$result->data->shipping_no;
                      //$file_name='FODEL-AWB-'.$awb_no.'.pdf';
                      $file_name =  'TEMPAWB.pdf';

                        
                        $this->insert_delivery($awb_no,$file_name);
                         $message='<div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h4><i class="icon fa fa-warning"></i> Success!</h4>Booking is Done.
                                </div>';
                                $status = 1;
                                
                                ///UPDATE JOB EXECUTION AS DONE 
                                $this->general_model->updateExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);

                           

                     }else{
                     $curl_error = $response."!";
                      $message='<div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Alert!</h4>'.$curl_error.'.
                           </div>';
                            $status = 0;
                        //DELTE JOB EXECUTION FOR THIS PROCESS
                        $this->general_model->removeExecutionState('add','online_booking_submit',uri_string().'/'.$request_key);

                  }
                
                }  
            
            $data=array('status'=>$status,'awb_no'=>$awb_no,'file'=>'uploads/awb/pdf/'.$file_name,'delivery_id'=>0,'message'=>$message);

                    return $data;  
       

  }




    function getOnlneBookingResponse($order_id,$courier_id)
    {


        $query = $this->db->query("select DeliveryId,AwbNumber,CourierAwbFile,CourierName from deliveries where CourierId='$courier_id' and OrderId='$order_id' ");
        

        if($query->num_rows()>0)
        {
            $data = $query->row();

            $message='<div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h4><i class="icon fa fa-warning"></i> Success!</h4>'.$data->CourierName.' Booking has been  Done.
                      </div>';
                    $status = 1;
            
            $awb_no = $data->AwbNumber;
           
            $data=array('status'=>$status,'awb_no'=>$awb_no,'file'=>'uploads/awb/pdf/'.$data->CourierAwbFile,'delivery_id'=>$data->DeliveryId,'message'=>$message);
        }
        else
        {

            $message='<div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h4><i class="icon fa fa-warning"></i> Error!</h4>There is some Error With This Request Please Reload the Page And Try Again or Contact IT team .
                      </div>';
                    $status = 0;
            $data=array('status'=>$status,'message'=>$message);                    

        }

                    

                    return $data;
    }


    function insert_delivery($awb_number,$courier_awb_file)
    {

              ///SESSION INFORMATION
          $session                =   $this->session->userdata('logged_in');
          $added_by               =   $session['user_full_name'];
          $added_by_id            =   $session['user_id'];


         $this->load->model('order_model');
             $order_id = $this->input->post('order_id');
             $order_object = $this->order_model->getOrderTableInfoById($order_id);
             

         $this->load->model('courier_model');
             $courier_id = $this->input->post('courier_id');
             $courier_object = $this->courier_model->getCourierInfoById($courier_id);

      
        $delivery_data_array=array();
        

        $shipper_country_name    =  $this->input->post('shipper_country_name');
        $shipper_country_code    =  $this->input->post('shipper_country_code');
        $shipper_city            =  $this->input->post('shipper_city');
        $shipper_contact_name    =  $this->input->post('shipper_contact_name');
        $shipper_company_name    =  $this->input->post('shipper_company_name');
        $shipper_adderss_line    =  $this->input->post('shipper_adderss_line1');
        $shipper_phone_number    =  $this->input->post('shipper_phone_number');
        $shipper_email_adderss   =  $this->input->post('shipper_email_adderss');

        $consignee_country_name  =  $this->input->post('consignee_country_name');
        $consignee_country       =  $this->input->post('consignee_country');
        $consignee_city          =  $this->input->post('consignee_city');
        $consignee_postal_code   =  $this->input->post('consignee_postal_code');
        $consignee_person_name   =  $this->input->post('consignee_person_name');
        $consignee_company_name  =  $this->input->post('consignee_company_name');
        $consignee_address_line1 =  $this->input->post('consignee_address_line1');
        $consignee_email         =  $this->input->post('consignee_email');
        $consignee_phone         =  $this->input->post('consignee_phone');
        $consignee_fax           =  $this->input->post('consignee_fax');
        
        $use_local_awb = $this->input->post('use_local_awb');

        if($use_local_awb==1)
        {
            $awb_number    =  $this->input->post('awb_number');
        }


        $pack_pieces             =  $this->input->post('pack_pieces');
        $pack_weight             =  $this->input->post('pack_weight');
        $pack_reference          =  $this->input->post('pack_reference');
        $carraige_value          =  $this->input->post('carraige_value');
        $customs_value           =  $this->input->post('customs_value');
        $insurance_value         =  $this->input->post('insurance_value');
        $cod_amount              =  $this->input->post('cod_amount');
        $pack_description        =  $this->input->post('pack_description');
        $special_instructions    =  $this->input->post('special_instructions');
        $item_sku                =  $this->input->post('item_sku');
        $item_name               =  $this->input->post('item_name');
        $item_qty                =  $this->input->post('item_qty');
        $item_price              =  $this->input->post('item_price');
        $hs_remarks_text         =  $this->input->post('hs_remarks_text');
        $request_key             =  $this->input->post('request_key');







        $delivery_master_insert_array = array(
                'ShipperName'       => $shipper_contact_name,
                'ShipperAddress'    => $shipper_adderss_line,
                'ShipperCompany'    => $shipper_company_name,
                'ShipperTelephone'  => $shipper_phone_number,
                'ShipperEmail'      => $shipper_email_adderss,
                
                'ReceiverName'          => $consignee_person_name,
                'ReceiverAddress'       => $consignee_address_line1,
                'ReceiverCompany'       => $consignee_company_name,
                'ReceiverTelephone'     => $consignee_phone,
                'ReceiverFax'           => $consignee_fax,
                'ReceiverDestination'   => $consignee_country_name,
                'ReceiverCity'          => $consignee_city,
                'ReceiverEmail'         => $consignee_email,

                'OrderSource'           => $order_object->OrderSource,
                'OrderSourceId'         => $order_object->OrderSourceId,
                'OrderId'               => $order_object->OrderId,
                'OrderNumber'           => $order_object->OrderNumber,
                'OrderReferenceNumber'  => $order_object->OrderReferenceNumber,
                'PaymentMethod'         => $order_object->PaymentMethod,
                'Currency'              => $order_object->CustomerCurrency,
                'Remarks'               => $hs_remarks_text,

                'CourierName'           => $courier_object->CourierTitle,
                'CourierId'             => $courier_object->CourierId,
                'ShipmentReference'     => $pack_reference,
                'AwbNumber'             => $awb_number,

                'DeliveryAmount'        => $cod_amount,
                'CourierAwbFile'        => $courier_awb_file,

                'InsertedBy'            => $added_by,
                'InsertedById'          => $added_by_id,
                'RequestKey'            => $request_key,
                'ShipmentStatus'        => 'booked'

            );

            $this->db->insert('deliveries',$delivery_master_insert_array);
            $delivery_id= $this->db->insert_id();

     
             $this->load->model('stock_model');
             $stock_out_items = $this->stock_model->getStockOutItemsByOrderId($order_id);

            $delivery_item_array = $ItemElements =  array();


    foreach ($stock_out_items as $key => $stock_object)
        {

                  $delivery_item_array[] = array(
                                'sku'=>$stock_object->ItemSku,
                                'order_qty'=>str_replace('-','',$stock_object->Qty),
                                'name'=>$stock_object->ItemTitle
                            );

                    $ItemElements[] = array(
                        'DeliveryId'   =>    $delivery_id,
                        'ItemSku'     =>    $stock_object->ItemSku,
                        'ItemTitle'   =>    $stock_object->ItemTitle,
                        'QtySent'   =>    str_replace('-','',$stock_object->Qty),
                        'OrderNumber'=> $order_object->OrderNumber,
                        'OrderReferenceNumber'=> $order_object->OrderReferenceNumber,
                        'AwbNumber'=>$awb_number,
                        'OrderId'=>$order_object->OrderId
                        );            

        }



       $ItemResult=$this->db->insert_batch('deliveries_items', $ItemElements);
     


        $this->db->where('DeliveryId',$delivery_id);
        $this->db->update('deliveries',array('ItemsInfo'=>json_encode($delivery_item_array)));


        ///UPDATE ORDER SHIPMENT STATUS AS BOOKED
        $this->db->where('OrderId',$order_object->OrderId);
        $this->db->update('orders',array('ShipmentStatus'=>'booked'));

        //INSERT ORDER DELIVERY REMARKS
        $this->general_model->insertRemarks('order_delivery',$courier_object->CourierTitle.'Booking has been done with AWB '.$awb_number,$order_object->OrderId);


    }


    function getDeliveryJsonBySearch($q)
    {
        $Cond=" Where AwbNumber Like '%$q%'";


        $query = $this->db->query("select DeliveryId from deliveries $Cond");
        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[]=   $value;
            }
            
        }

        $this->load->model('rto_model');
        $rto_count = $this->rto_model->countRtoByAwb($q);
        $array['rto_count']=$rto_count;


        header('Content-Type: application/json');
        return json_encode($array);
    }

    public function getDeliveryInfoById($delivery_id=0)
    {
        $this->db->where('DeliveryId',$delivery_id);
        return $this->db->get('deliveries')->row();
    }

    public function getDeliveryInfoByOrderId($order_id=0)
    {
        $this->db->where('OrderId',$order_id);
        return $this->db->get('deliveries');
    }

    public function getDeliveryItemsByDeliveryId($delivery_id=0)
    {
        $this->db->where('DeliveryId',$delivery_id);
        return $this->db->get('deliveries_items')->result_array();
    }    




function getSuggestedDeliveryTimes()
{
        $query = $this->db->query("SELECT TimeValue FROM `delivery_times` ");
        $d = $query->result();
         $array=array();
      
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->TimeValue]=$value->TimeValue;
            }
        }
        else{
            $array[0]='No Suggest Time Present';
        }
        return $array;
    }






}
    ?>