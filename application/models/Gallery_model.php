<?php

class gallery_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }


     function getImagefoById($RecordFor,$RecordForId)
     {
            $this->db->where('RecordForId',$RecordForId);
            $this->db->where('RecordFor',$RecordFor);
            $this->db->select('*');
            return $this->db->get('gallery')->result_array(); 
     }

     function insertImage($RecordFor,$RecordForId,$FileName)
     {
            $array =array('RecordForId'=>$RecordForId,'RecordFor'=>$RecordFor,'ImagePath'=>$FileName);
            $this->db->insert('gallery',$array);

/*



        $this->load->library('image_lib');
        $config['image_library']    = 'gd2';
        $config['source_image']     = $db_img_array[0];
        $config['new_image']        = $image_thumb;     
        $config['create_thumb']     = TRUE;
        $config['maintain_ratio']   = TRUE;
        $config['width']            = 75;
        $config['height']           = 75;

        $this->image_lib->clear();
        $this->image_lib->initialize($config);
        $this->image_lib->resize();

        $this->db->where('ProductId',$ProductId);
        $this->db->update('products',array('Thumbnail'=>'uploads/products/images/thumb/'.$thumb_name.'_thumb.png'));*/

     }




}
    ?>