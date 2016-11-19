<?php
Class Mail_model extends CI_Model
{
	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

 


   function send_mail($from,$to,$subject,$body)
    {


				$config = Array(
				    'protocol' => 'smtp',
				    'smtp_host' => 'smtp.qq.com',
				    'smtp_port' => 465,
				    'smtp_user' => '304777898@qq.com',
				    'smtp_pass' => 'xy8881660',
				    'mailtype'  => 'html', 
				    'charset'   => 'iso-8859-1'
				);
				$this->load->library('email', $config);
				$this->email->set_newline("\r\n");


			$this->email->from($from, 'Omigo Dev');
			$this->email->to($to);
			$this->email->cc('naseem@kingsou.com');

			$this->email->subject($subject);
			$this->email->message('Testing the email class.');

			$this->email->send();

			echo $this->email->print_debugger();

    }

}