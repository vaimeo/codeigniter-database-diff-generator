# ci-database-diff-generator
codeigniter database diff sql statement generator 


#1 download this repo
#2 configure basic application configs application/config/config.php
#3 configure database config add source and target database 



$db['default'] = array(
	'dsn'	=> '',
	'hostname' => 'localhost',
	'username' => 'root',
	'password' => '',
	'database' => 'source_db',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => APPPATH.'cache/db/',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt'  => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);



$db['target_db']['dsn']	= '';
	$db['target_db']['hostname'] = 'localhost';
	$db['target_db']['username'] = 'root';
	$db['target_db']['password'] = '';
	$db['target_db']['database'] = 'taraget-db';
	$db['target_db']['dbdriver'] = 'mysqli';
	$db['target_db']['dbprefix'] = '';
	$db['target_db']['pconnect'] = FALSE;
	$db['target_db']['db_debug'] = (ENVIRONMENT !='production');
	$db['target_db']['cache_on'] = TRUE;
	$db['target_db']['cachedir'] = '';
	$db['target_db']['char_set'] = 'utf8';
	$db['target_db']['dbcollat'] = 'utf8_general_ci';
	$db['target_db']['swap_pre'] = '';
	$db['target_db']['encrypt']  = FALSE;
	$db['target_db']['compress'] = FALSE;
	$db['target_db']['stricton'] = FALSE;
	$db['target_db']['failover'] = array();
	$db['target_db']['save_queries'] = TRUE;


open the URL yoururl.com/ci-database-diff-generator/

