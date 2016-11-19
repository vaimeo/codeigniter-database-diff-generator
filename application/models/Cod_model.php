<?php
Class Cod_model extends CI_Model
{
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    public function getCouriersList()
    {
    	$this->db->select('*');
		$this->db->from('couriers');
		return $this->db->get()->result_array();
    }

    function count_all($courier_id=0){
       
        $query = $this->db->query('SELECT * FROM courier_transactions where CourierId='.$courier_id);
        return $query->num_rows();
      
    }

    public function getTransactionsByCourier($courier_id,$perPage,$offset)
    {
       

        $this->db->select('*');
        $this->db->from('courier_transactions');
        $this->db->where('CourierId',$courier_id);
        $this->db->order_by("DATE(TransactionDate) ASC,CourierTransactionId ASC");
        $this->db->limit($perPage,$offset);
        //echo $this->db->get_compiled_select();
        return  $this->db->get()->result_array();
    }

    public function getCODStatement($courier_id,$st_date)
    {
       

        $this->db->select('*');
        $this->db->from('courier_transactions');
        $this->db->where('CourierId',$courier_id);
        $this->db->where('DATE(TransactionDate) < ',$st_date);
        $this->db->order_by("DATE(TransactionDate) ASC,CourierTransactionId ASC");
        // WHERE DATE(TransactionDate) >= DATE(NOW()) - INTERVAL 7 DAY

        return  $this->db->get()->result_array();
    }
    public function getPaymentDays($courier_id)
    {
        $this->db->select('PaymentDays');
        $this->db->from('couriers');
        $this->db->where('CourierId',$courier_id);
        return $this->db->get()->row()->PaymentDays;

    }

public function setDaysPeriod($courier_id,$PaymentDays)
    {

         $PaymentDays = ($PaymentDays) ? $PaymentDays : 7;

            $this->db->select('*');
            $this->db->from('courier_transactions');
            $this->db->where('CourierId',$courier_id);
            
            $this->db->order_by("DATE(TransactionDate) ASC,CourierTransactionId ASC");
           

            $res = $this->db->get()->result_array();

            if(count($res))
            {
                $date1 = date('Y-m-d', strtotime($res[0]['TransactionDate']));
                $preId = 0;
                foreach ($res as $row)
                {
                    $CourierTransactionId = $row['CourierTransactionId']; 
                    $date2 = date('Y-m-d', strtotime($row['TransactionDate']));
                    $days = get_days($date1,$date2);
                    if($days >= $PaymentDays)//check payment after these days
                        {
                          $date1 = $date2;
                          // update field,DaysPeriod=1

                           $data = array(
                              
                               'DaysPeriod' => 1
                            );


                        $this->db->where('PaymentType',0);
                        $this->db->where('CourierTransactionId',$preId);
                        $this->db->update('courier_transactions',$data); 
                        }

                    $preId = $CourierTransactionId;

                }
            }
    }
    
    public function getRefreshData($courier_id)
    {
            $this->db->select('*');
            $this->db->from('courier_transactions_temp');
            $this->db->where('CourierId',$courier_id);
            $this->db->where('IsInserted',0);
            // $this->db->order_by("DATE(TransactionDate)", "ASC");
            //  $this->db->order_by("CourierTransactionTempId", "ASC");
            $this->db->order_by("DATE(TransactionDate) ASC,CourierTransactionTempId ASC");
           

            $res = $this->db->get()->result_array();
            if(count($res))
            {
                 $data_insert = $courier_id_array= array();
                    $TotalBalance = 0;
                foreach ($res as $row)
                {
                  
                  $courier_id_array[] = $row['CourierTransactionTempId']; 

                    $TotalBalance = $TotalBalance + trim($row['Amount']);

                    $data_insert[] = array(
                    'AwbNumber' => $row['AwbNumber'],
                    'OrderReferenceNumber' => $row['OrderReferenceNumber'],
                    'TransactionDesc' => $row['TransactionDesc'],
                    'CourierId' => trim($row['CourierId']),
                    'Amount' => trim($row['Amount']),
                    'Currency' => $row['Currency'],
                    'TransactionDate' => trim($row['TransactionDate']),
                    'TotalBalance' => trim($TotalBalance)
                    );

                }

                //inserting to trans
                $this->db->insert_batch('courier_transactions', $data_insert); 

           /*
                echo "<pre>";
                print_r($data_insert);
                echo "</pre>";*/
                $this->db->where('CourierTransactionTempId in ('.implode(',', $courier_id_array).')');
               $this->db->update('courier_transactions_temp',array('IsInserted'=>1)); 

            }
           return true;
               

    }
    public function SavePayment()
    {
        $credit_amount= $this->input->post('credit_amount');
        $payment_type= $this->input->post('payment_type');
        $payment_remarks= $this->input->post('payment_remarks');

        $TransactionDate= $this->input->post('TransactionDate');
        $courier_id= $this->input->post('courier_id');
        $PaymentReference = $this->input->post('PaymentReference');
        $CourierTransactionId= trim($this->input->post('CourierTransactionId'));

        //TODO: check if amount is less than total payment
        
        //  $this->db->select('TotalBalance');
        // $this->db->from('courier_transactions');
        // $this->db->where('CourierTransactionId',$CourierTransactionId);
        //$this->db->where('CourierId',$courier_id);
        // $this->db->where('DATE(TransactionDate) < ',$TransactionDate);
        // $this->db->order_by("DATE(TransactionDate) DESC,CourierTransactionId DESC");
        //$this->db->limit(1);
        //$tot_amount =  $this->db->get()->row()->TotalBalance;


        //TODO, need check if less payment is done that total

       // $TotalBalance = $tot_amount - $credit_amount;

        //if($TotalBalance<1)  $TotalBalance = 0;
        $TotalBalance = 0;

        $data = array(
                'TransactionDesc' => $payment_remarks,
                'CourierId' => $courier_id,
                'Amount' => $credit_amount,
                'TransactionDate' => $TransactionDate,
                'PaymentType' => 1,
                'PaymentReference' => $PaymentReference,
                'TotalBalance' => $TotalBalance,
                'DaysPeriod' => 2,//showing recieved
                );
        $this->db->insert('courier_transactions',$data);
        $last_id = $this->db->insert_id();
        // if($this->db->affected_rows())
        // {
            //first update all records IsPaid to 1 & totalbalance shefted
             $this->db->select('*');
             $this->db->from('courier_transactions');
            $this->db->where('CourierId',$courier_id);
            //$this->db->where('DATE(TransactionDate) > ', date('Y-m-d'));
            //$this->db->where('DATE(TransactionDate) >', $TransactionDate);
             $this->db->where('CourierTransactionId >',$CourierTransactionId);
             // $this->db->where('CourierTransactionId !=',$last_id);
            $this->db->order_by("DATE(TransactionDate) ASC,CourierTransactionId ASC");
             $TotalBalance =  0;
            $res = $this->db->get()->result_array();
                foreach ($res as $row)
                {
                    //assume that we paid whole amount till date
                    if($row['PaymentType'] == 0)
                    {
                        $TotalBalance = $TotalBalance + trim($row['Amount']);
                        $data1 = '';
                       $data1 = array(
                        'TotalBalance' => $TotalBalance
                        ); 
                       $this->db->where('CourierTransactionId',$row['CourierTransactionId']);
                       $this->db->update('courier_transactions',$data1);

                    }
                       
                }
            

                // Do other things if you want here
                 $data_roww = array(
                        'DaysPeriod' => 2
                        ); 
                       $this->db->where('CourierTransactionId',trim($CourierTransactionId));
                       $this->db->update('courier_transactions',$data_roww);

                return true;
            

        // }else{
        //     return false;
        // }

    }
}