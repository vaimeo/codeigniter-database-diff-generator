<?php
Class user_model extends CI_Model
{
 function login($user_name, $password)
 {
   
   $this->db->select('UserId,UserName,UserPass,StationId,PermissonKeys,FullName,ProfilePicture,DepartmentId,
DesignationId');
   $this->db->where('UserName',$user_name);
   $query = $this->db->get('users');
   $res = $query->row();/*
   print_r($this->db->last_query());

    */

     $db_pass = $this->encryption->encrypt($password);

    if($query->num_rows() == 1)
    {

   //print_r($res->UserPass);

        $db_pass = $this->encryption->decrypt($res->UserPass);

       if($db_pass == $password)
       {
          return $res;  
       }
       else
       {
         return false;
       }

    }
   else
   {
     return false;
   }
 }

 function getUserDynamicInfo($UserId)
 {
        $this->db->where('UserId',$UserId);
        $this->db->select('ProfilePicture,UserName');
        return $this->db->get('users')->row(); 
 }

 
 function getUserInfoById($UserId)
 {
        $this->db->where('UserId',$UserId);
        return $this->db->get('users')->row(); 
 }


function getUserInfo()
{

        $query = $this->db->query('select UserId,UserName,UserPass,NativeFullName,FullName,Department from users');
        $d= $query->result();
        return $d;
}


function updatePermisions($user_id)
 {
    $user_permisson   =   $this->input->post('user_permisson');
    if(count($user_permisson)>0){
      $this->db->where('UserId',$user_id);
      $this->db->update('users',array('PermissonKeys'=>json_encode($user_permisson)));
      $this->db->query("delete from users_sessions where data like '%user_id-$user_id%'");
    }
 }

   
 function checkUserDuplicateInfo($clause)
 {
    $this->db->where($clause);
    $result = $this->db->get('users');
    $num_rows = $result->num_rows();
    return  array($num_rows,$result->result_array());
 }


 function addSubmit()
 {
   
        $full_name      =   $this->input->post('full_name');
        $user_email     =   $this->input->post('user_email');
        $user_city      =   $this->input->post('user_city');
        $user_password  =   $this->input->post('user_password');

        $uc                 = explode('-', $this->input->post('user_country'));

        $country_code       =       $uc[0];
        $country            =       $uc[1];

   
        $dpt                = explode('-', $this->input->post('department'));
        $department_id      =       $dpt[0];
        $department         =       $dpt[1];

   
        $st                 = explode('-', $this->input->post('station'));
        $station_id         =       $st[0];
        $station            =       $st[1];

        
        $user_address   =       $this->input->post('user_address');
        $session        =       $this->session->userdata('logged_in');
        $added_by       =       $session['user_full_name'];
        $added_by_id    =       $session['user_id'];

        $native_full_name    =      $this->input->post('native_full_name');
        $short_name          =      $this->input->post('short_name');



        //INSERT VENDOR DATA 

        $update_array = array(
            'FullName'              =>  $full_name,
            'UserEmail'             =>  $user_email, 
            'UserName'              =>  $user_email,
            'NativeFullName'        =>  $native_full_name,  
            'ShortName'             =>  $short_name,  
            'UserPass'              =>  $db_pass = $this->encryption->encrypt($user_password), 
            'CountryCode'           =>  $country_code,
            'Country'               =>  $country,
            'City'                  =>  $user_city,
            'StationId'             =>  $station_id,
            'Station'               =>  $station,
            'DepartmentId'          =>  $department_id,
            'Department'            =>  $department,
            'UserAddress'           =>  $user_address,
            'InsertedBy'            =>  $added_by,
            'InsertedById'          =>  $added_by_id
            );

        $this->db->insert('users',$update_array);
        $user_id = $this->db->insert_id();
        $this->updatePermisions($user_id);

     return true;


 }


 function editSubmit($user_id)
 {
   
        $full_name      =   $this->input->post('full_name');
        $user_email     =   $this->input->post('user_email');
        $user_city      =   $this->input->post('user_city');

        $uc                 = explode('-', $this->input->post('user_country'));

        $country_code       =       $uc[0];
        $country            =       $uc[1];

   
        $dpt                = explode('-', $this->input->post('department'));
        $department_id      =       $dpt[0];
        $department         =       $dpt[1];

   
        $st                 = explode('-', $this->input->post('station'));
        $station_id         =       $st[0];
        $station            =       $st[1];

        
        $user_address   =   $this->input->post('user_address');

        $session        =       $this->session->userdata('logged_in');
        $added_by       =       $session['user_full_name'];
        $added_by_id    =       $session['user_id'];

        $native_full_name    =      $this->input->post('native_full_name');
        $short_name          =      $this->input->post('short_name');

        

        //INSERT VENDOR DATA 

        $update_array = array(
            'FullName'              =>  $full_name,
            'UserEmail'             =>  $user_email, 
            'NativeFullName'        =>  $native_full_name,  
            'ShortName'             =>  $short_name,  
            'CountryCode'           =>  $country_code,
            'Country'               =>  $country,
            'City'                  =>  $user_city,
            'StationId'             =>  $station_id,
            'Station'               =>  $station,
            'DepartmentId'          =>  $department_id,
            'Department'            =>  $department,
            'UserAddress'           =>  $user_address,
            'InsertedBy'            =>  $added_by,
            'InsertedById'          =>  $added_by_id
            );

        $this->db->where('UserId',$user_id);
        $this->db->update('users',$update_array);


     return true;


 }




function getUsersDdByDepartment($DepartmentId)
 {

      $query = $this->db->query("select * from users where DepartmentId='$DepartmentId'");
      $d = $query->result();
      $array=array();
      if($query->num_rows()>0){
        foreach ($d as $key => $value) {
          $array[$value->UserId]=$value->FullName;
        }
      }
      else{
        $array[0]='No Record Found ';
      }
      return $array;
    }

 

 function getUsersByDepartmentInfoJson($DepartmentId,$q)
 {
    $Cond = " where FullName like '%$q%'";


    $query = $this->db->query("select UserId as id,FullName as text from users $Cond");
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




 function resetPassword($user_name, $password,$code)
 {
  

  $this->db->select('UserEmail');
  $this->db->where('UserEmail',$user_name);
  $this->db->where('PassResetNumber',$code);
  $query = $this->db->get('users');
  $res = $query->row();

    if($query->num_rows() == 1)
    {

      $db_pass = $this->encryption->encrypt($password);
      $status = array('UserPass' => $db_pass,'PassResetNumber' => '');
      $this->db->where('UserEmail',$user_name)->update('users', $status);
      return true;

    }
   else
   {
     return false;
   }

  
 }

 


 
}
?>