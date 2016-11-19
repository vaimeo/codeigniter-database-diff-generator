<?php
Class purchaser_model extends CI_Model
{


 function getPurchaseOrders($cond)
 {
    if($cond!='')
    {
          $this->db->where($cond);
    }
    $this->db->select('*');
    return $this->db->get('purchase_order')->result_array(); 
 }


 function getOrderItemInfoById($OrderId)
 {
        $this->db->where('PurchaseOrderId',$OrderId);
        $this->db->select('*');
        return $this->db->get('purchase_order_item')->result_array(); 
 }

 function getOrderItemInfoByIds($OrderIds)
 {
        $this->db->where('PurchaseOrderId in ('.$OrderIds.')');
        $this->db->order_by('ItemSku');
        $this->db->select('*');
        return $this->db->get('purchase_order_item')->result_array(); 
 }




 

}
?>