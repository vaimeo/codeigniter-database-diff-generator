<?php
Class category_model extends CI_Model
{



function getCategories($Level=1,$return_master_id_as_array_key=false)
{
    $query = $this->db->query("select name,parent_id,CategoryId from categories where Level='$Level'");
    $d = $query->result();
    $array=array();
    if($query->num_rows()>0){
    	if($return_master_id_as_array_key)
    	{
	        foreach ($d as $key => $value) {
	            $array[$value->parent_id][]=   $value;
	        }
    	}
    	else
    	{
	        foreach ($d as $key => $value) {
	            $array[]=   $value;
	        }
    	}
        
    }
    return $array;
}




function getCategoriesNameByIds($category_ids)
{
    $query = $this->db->query("select name from categories where CategoryId in ('$category_ids')");
    $d = $query->result();
    $array=array();
    if($query->num_rows()>0){
         foreach ($d as $key => $value) {
                $array[]=   $value->name;
            }
    }
    return $array;
}










}
?>