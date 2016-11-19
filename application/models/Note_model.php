<?php

class note_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

 
/*
    function getOrderNotes($OrderId){
        /// THIS FUNCTION WILL GET ORDER HISTORY IN THE FORMAT WE NEED ON FRONT END
        $this->db->where('NoteTypeId',$OrderId);
        $this->db->select('DATE(`InsertedDate`) AS date_part,
            TIME(`InsertedDate`) AS time_part,
            NoteDescrption,
            UserName
            ');
        $d  =   $this->db->get('notes');
        $data_array=array();
        if($d->num_rows()>0){
            foreach ($d->result() as $key => $value) {
                $data_array[$value->date_part][] = $value;
            }
        }
        return $data_array;
    }*/


  function getOrderNotes($OrderId){
        /// THIS FUNCTION WILL GET ORDER HISTORY IN THE FORMAT WE NEED ON FRONT END
        $this->db->where('NoteTypeId',$OrderId);
        $this->db->where('NoteType','order');
        $d  =   $this->db->get('notes_info_view');
        return $d;
    }
}
    ?>