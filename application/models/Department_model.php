<?php
Class department_model extends CI_Model
{




 function getDepartmentInfoById($DepartmentId)
 {
        $this->db->where('DepartmentId',$DepartmentId);
        $this->db->select('*');
        return $this->db->get('departments')->row(); 
 }



   function getAdvDepartments()
    {
        $query = $this->db->query("select * from departments");
        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->DepartmentId.'-'.$value->DepartmentTitle]=$value->DepartmentTitle;
            }
        }
        else{
            $array[0]='Departments Not Found';
        }
        return $array;
    }





}
?>