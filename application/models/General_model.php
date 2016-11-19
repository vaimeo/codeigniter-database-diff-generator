<?php
Class general_model extends CI_Model
{



function getAttributeSets()
{
    $query = $this->db->query("select * from attribute_set");
    $d = $query->result();
    $array=array();
    if($query->num_rows()>0){
    	
	        foreach ($d as $key => $value) {
	            $array[]=   $value;
	        }
        
    }
    return $array;
}



 function getStationInfoById($StationId)
 {
        $this->db->where('StationId',$StationId);
        $this->db->select('*');
        return $this->db->get('stations')->row(); 
 }




 function getOrderSourceInfo($OrderSource)
 {
        $this->db->where('OrderSourceTitle',$OrderSource);
        $this->db->select('*');
        return $this->db->get('order_sources')->row(); 
 }




 function getBrandsInfoJson($q)
 {
    $Cond = " where BrandTitle like '%$q%'";

    $query = $this->db->query("select concat(BrandId,'-',BrandTitle) as id,BrandTitle as text from brands $Cond");
    /*$this->db->cache_on();*/
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




    function validateInsertRequest($table_name,$key)
    {
        $r= true;

        $d = $this->db->query("select count(*) as TotalRecords from $table_name where RequestKey='$key'")->row();
        if($d->TotalRecords>0)
        {
            $r= false;
        }

        return $r;
    }



    function validateExecutionState($crud_action,$request_key,$request_url)
    {
     
        $r= true;

        $e = $this->db->query("select JobStatus from request_executions where RequestUrl='$request_url' and RequestKey='$request_key' and CrudAction='$crud_action'");

        if($e->num_rows()>0)
        {
            $d=$e->row();

            if($d->JobStatus=='processing')
            {
                $r = false;
            }
        }
      
        
        return $r;
    }



    function validateExecutionDoneState($crud_action,$request_key,$request_url)
    {
     
        $r= true;

        $e = $this->db->query("select JobStatus from request_executions where RequestUrl='$request_url' and RequestKey='$request_key' and CrudAction='$crud_action'");

        if($e->num_rows()>0)
        {
            $d=$e->row();
            if($d->JobStatus=='done')
            {

                $r = false;
            }
        }
      
        
        return $r;
    }



  function insertExecutionState($crud_action,$request_key,$request_url)
    {
//INSERT JOB IN EXECUTION STATE

            $job_insert_array = array(
                    'RequestUrl' => $request_url,
                    'RequestKey' => $request_key,
                    'CrudAction' => $crud_action,
                    'JobStatus'  => 'processing'
                );
            $this->db->insert('request_executions',$job_insert_array);
    }

  function removeExecutionState($crud_action,$request_key,$request_url)
    {
        //INSERT JOB IN EXECUTION STATE
        $this->db->query("delete from  request_executions where RequestUrl='$request_url' and RequestKey='$request_key' and CrudAction='$crud_action'");

    }



    function updateExecutionState($crud_action,$request_key,$request_url)
    {
     
        $r= false;

        $e = $this->db->query("select JobStatus from request_executions where RequestUrl='$request_url' and RequestKey='$request_key' and CrudAction='$crud_action'");

        if($e->num_rows()>0)
        {
            $d=$e->row();
            if($d->JobStatus=='processing')
            {
                $this->db->update('request_executions',array('JobStatus'  => 'done'));
                $r = true;
            }
        }

        
        return $r;
    }





    function insertRemarks($remark_type,$remark_desc,$remark_type_id)
    {
                  ///SESSION INFORMATION
          $session                =   $this->session->userdata('logged_in');
          $added_by               =   $session['user_full_name'];
          $added_by_id            =   $session['user_id'];

        $this->db->insert('remarks',array('RemarkType'=>$remark_type,'RemarkTypeId'=>$remark_type_id,'Desc'=>$remark_desc,'InsertedBy'=>$added_by));
    }




// shafiq code
    public function get_delivery_json_by_search(){
        $this->load->model('rto_model');
        $d=$this->rto_model->getDeliveryJsonBySearch($this->input->get('term'));
        print_r($d);
    }



}
?>