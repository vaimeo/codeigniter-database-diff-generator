<?php
Class document_model extends CI_Model
{
 


  function getDocuments($RecordFor,$RecordForId)
 {
        $this->db->where('RecordFor',$RecordFor);
        $this->db->where('RecordForId',$RecordForId);
        $this->db->select('*');
        return $this->db->get('documents'); 
 }
 



   function getDocumentTypes($CountryId)
 {
    	$query = $this->db->query("select * from vendor_document_type_config where AllowedCountry='$CountryId'");
    	$d = $query->result();
    	$array=array();
    	if($query->num_rows()>0){
	    	foreach ($d as $key => $value) {
	    		$array[$value->DocumentType]=$value->DocumentType;
	    	}
    	}
    	else{
    		$array[0]='Document Typeb Not Found For  Vendor Country';
    	}
    	return $array;
    }


 function insertDocument($RecordFor,$RecordForId,$DocumentPath,$DocumentTitle,$DocumentType)
 {

		//INSERT VENDOR AGENT DATA
        $document_insert_array = array(
            'DocumentTitle'         =>  $DocumentTitle,
            'DocumentPath'         	=>  $DocumentPath,
            'RecordFor'            	=>  $RecordFor,
            'RecordForId'           =>  $RecordForId,
            'DocumentType'          =>  $DocumentType
            );

     $this->db->insert('documents',$document_insert_array);

     return true;


 }


}
?>