<?php
Class station_model extends CI_Model
{




 function getStationInfoById($StationId)
 {
        $this->db->where('StationId',$StationId);
        $this->db->select('*');
        return $this->db->get('stations')->row(); 
 }



   function getAdvStations()
    {
        $query = $this->db->query("select * from stations");
        $d = $query->result();
        $array=array();
        if($query->num_rows()>0){
            foreach ($d as $key => $value) {
                $array[$value->StationId.'-'.$value->StationTitle]=$value->StationTitle;
            }
        }
        else{
            $array[0]='Departments Not Found';
        }
        return $array;
    }





}
?>