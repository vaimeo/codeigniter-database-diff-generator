<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*http://simplehtmldom.sourceforge.net/manual_api.htm*/
class Database_diff extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{


   			$this->target_db = $this->load->database('target_db', TRUE);

			$tables = $this->db->list_tables();
			echo '<table border="1" width="100%">';
				echo "<tr>";
					echo '<td >';
						echo '<h2>Source '.$this->db->database.'</h2>';
					echo "</td>";

					echo '<td >';
						echo '<h2>Target '.$this->target_db->database.'</h2>';
					echo "</td>";
				echo "</tr>";


			$td_style='white';
			foreach ($tables as $table)
			{
				$alter_this_table=false;
				echo "<tr>";
					echo '<td colspan="2">';
				        echo '<h3>';
				        	echo $table;
				        echo '</h3>';
					echo "</td>";
				echo "</tr>";

			    	$filed_statement='';
			    	$table_exists = true;
					if (!$this->target_db->table_exists($table))
					{
					   $filed_statement="<i>Need to Add this table in Target Database</i><br>";

					   $table_exists = false;
					 
					}


			echo "<tr>";
					echo '<td  width="50%">';
			    	$fields = $this->db->field_data($table);

			    	$target_fields = array();
			    	if($table_exists)
					{
			    		$target_fields = $this->target_db->field_data($table);
			    	}
/*echo "<pre>";
	print_r($fields);
echo "</pre>";*/
					foreach ($fields as $field)
					{
	
					        echo $field->name;
							echo ' (';					
					        echo $field->type;
							echo ' ';					
					        echo $field->max_length;
							echo ' ';					
					        echo $field->primary_key;
							echo ')';
				        echo '<br>';

					}
					echo "</td>";





			    	$target_fields = array();
			    	if($table_exists)
					{
			    		$target_fields = $this->target_db->field_data($table);
			    	}

					

						$add_statement = $create_statement = '';
						$d= $e= 0;
					foreach ($fields as $field)
					{
						if($table_exists)
						{

							if (!$this->target_db->field_exists($field->name,$table))
								{
									$d++;
									 $alter_this_table=true;

												$default_value='   NOT NULL';
												if($field->default!=NULL)
												{
													$default_value ='  DEFAULT '.$field->default;
													if(is_string($field->default))
													{
														$default_value ="  DEFAULT '".$field->default."'";
													}
												}



												$lenth_string='  ('.$field->max_length.')  ';
												if($field->type=='timestamp'||$field->type=='datetime'||$field->type=='text')
												{
													$lenth_string=' ';
												}

												if($d==1)
												{
													$add_statement.=' ADD '.$field->name.'  '.$field->type.$lenth_string.$default_value;
												}
												else
												{
													$add_statement.=', ADD '.$field->name.'  '.$field->type.$lenth_string.$default_value;
												}

								}
								else
								{
									foreach ($target_fields as $t_field) {
/*										$filed_statement='';
*/

										if($field->name==$t_field->name)
										{

											$alter_this_field=false;
											$alter_statement='';

											if($field->type!=$t_field->type||$field->default!=$t_field->default||$field->max_length!=$t_field->max_length)
											{
												$e++;

												$default_value='   NOT NULL';
												if($field->default!=NULL)
												{
													$default_value ='  DEFAULT '.$field->default;
													if(is_string($field->default))
													{
														$default_value ="  DEFAULT '".$field->default."'";
													}
												}


												$lenth_string='  ('.$field->max_length.')  ';
												if($field->type=='timestamp'||$field->type=='datetime'||$field->type=='text')
												{
													$lenth_string=' ';
												}

												if($e==1)
												{
													$alter_statement.=' '.$field->name.'  '.$field->type.$lenth_string.$default_value;
												}
												else
												{
													$alter_statement.=','.$field->name.'  '.$field->type.$lenth_string.$default_value;
												}

												$alter_this_field=true;
												$alter_this_table=true;
											}




											if($alter_this_field)
											{
												$filed_statement.= '  '.$alter_statement.'<br>';
											}
										}
									}
								}

								
						

						}
						else
						{

									$d++;
									 $alter_this_table=false;

												$default_value='   NOT NULL';
												if($field->default!=NULL)
												{
													$default_value ='  DEFAULT '.$field->default;
													if(is_string($field->default))
													{
														$default_value ="  DEFAULT '".$field->default."'";
													}
												}



												$lenth_string='  ('.$field->max_length.')  ';
												if($field->type=='timestamp'||$field->type=='datetime'||$field->type=='text')
												{
													$lenth_string=' ';
												}

												if($d==1)
												{
													$create_statement.=' '.$field->name.'  '.$field->type.$lenth_string.$default_value;
												}
												else
												{
													$create_statement.=', '.$field->name.'  '.$field->type.$lenth_string.$default_value;
												}

								
						}





					}

					$td_style='white';

						if($alter_this_table)
						{
							$td_style='#F7BE65';
						}


						if(!$table_exists)
						{
							  $td_style='#E98A7E';
						}



					echo '<td  valign="top" style="background-color:'.$td_style.'">';
						
							//echo $filed_statement;

							echo '<table border="0">';
								echo '<tr>';
									echo '<td  valign="top">';

									echo '</td>';								
									echo '<td valign="top">';


							if($alter_this_table)
								{
									if($filed_statement!=null)
									{
										echo "ALTER TABLE ".$table.'  MODIFY ';
										echo $filed_statement;
										
									}
									if($add_statement);
									{
										echo "ALTER TABLE ".$table.' '.$add_statement;
									}


										

								}


								if(!$table_exists)
								{

									if($create_statement);
									{
										echo "CREATE TABLE ".$table.' '.$create_statement;
									}	
								}


								if(!($alter_this_table)&&!$create_statement)
								{
									echo "No Changes";
								}

									echo '</td>';								
								echo '</tr>';



							echo '</table>';


					echo '</td >';


			echo "</tr>";

					
			}
			echo "</table>";
		
	}


}
