<?php

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

require_once('api.php');
require_once('jwtpropio.php');
require_once('ExpiredException.php');
//var_dump( $_SERVER);

//echo 'ddf';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type,      Accept");
header("Content-Type: application/json");

$servername = "parrotinstance.cgnasseg9x7w.us-east-2.rds.amazonaws.com";
						$usernameServer = "parrotuser";
						$password = "CeSaRoRa1!";
			
						$mysqli = new mysqli($servername, $usernameServer, $password, "parrotweb");
						$mysqli->query("SET NAMES 'utf8'");

$pathInfo=$_SERVER["PATH_INFO"];

//echo 'PATH_INFO: ' . $pathInfo;

$arrPath=explode('/',$pathInfo);

//var_dump($arrPath);

if($arrPath[1]=='user')
{
	if($arrPath[2]=='login')
	{
		$api = new Api;
		$message=$api->login();
		echo $message;
	}
	else if($arrPath[2]=='logout')
	{
		
		$api = new Api;
		$message=$api->logout();
		
		echo $message;
		
	}
	
}
else if($arrPath[1]=='sales')//VENTAS ACUMULADAS
{
	$frequency='Daily'; //0-POR DIA, 1-POR SEMANA, 2-POR MES,4-POR ANO
	if($arrPath[2]!='')
	$frequency=$arrPath[2];
	
	$api = new Api;
	if($frequency=='Daily')
	{
		$year=$arrPath[3];
		$month=$arrPath[4];
		$day=$arrPath[5];
		
		if($year=='')
			$year=date("Y");
		if($month=='')
			$month=date("m");
		if($day=='')
			$day=date("d");
		if($month=='January')
			$month=1;
		if($month=='February')
			$month=2;
		if($month=='March')
			$month=3;
		if($month=='April')
			$month=4;
		if($month=='May')
			$month=5;
		if($month=='June')
			$month=6;
		if($month=='July')
			$month=7;
		if($month=='August')
			$month=8;
		if($month=='September')
			$month=9;
		if($month=='October')
			$month=10;
		if($month=='November')
			$month=11;
		if($month=='December')
			$month=12;
						
		$message=$api->getDailySales($year,$month,$day);
		echo $message;

						//$api->getDailySales();

	}
	else if($frequency=='Weekly')
	{
		$year=$arrPath[3];
		$week=$arrPath[4];

		if($year=='')
			$year=date("Y");
		if($week=='')
			$week=date("W")-1;
						
		if($month=='January')
			$month=1;
		if($month=='February')
			$month=2;
		if($month=='March')
			$month=3;
		if($month=='April')
			$month=4;
		if($month=='May')
			$month=5;
		if($month=='June')
			$month=6;
		if($month=='July')
			$month=7;
		if($month=='August')
			$month=8;
		if($month=='September')
			$month=9;
		if($month=='October')
			$month=10;
		if($month=='November')
			$month=11;
		if($month=='December')
			$month=12;

		$message=$api->getWeeklySales($year,$week);
		echo $message;

	}
	else if($frequency=='Monthly')//Mensual
	{
		$year=$arrPath[3];
		$month=$arrPath[4];
	
		if($year=='')
			$year=date("Y");
		if($month=='')
			$month=date("m");
						
		if($month=='January')
			$month=1;
		if($month=='February')
			$month=2;
		if($month=='March')
			$month=3;
		if($month=='April')
			$month=4;
		if($month=='May')
			$month=5;
		if($month=='June')
			$month=6;
		if($month=='July')
			$month=7;
		if($month=='August')
			$month=8;
		if($month=='September')
			$month=9;
		if($month=='October')
			$month=10;
		if($month=='November')
			$month=11;
		if($month=='December')
			$month=12;
		
		//echo 'antes';
		$message=$api->getMonthlySales($year,$month);
		//echo 'dep';
		echo $message;
		
	}
	else if($frequency=='Yearly')//Anual
	{
		$year=$arrPath[3];
		$message=$api->getYearlySales($year);
		echo $message;
		
	}
	
	
}	
else if($arrPath[1]=='branches')//VENTAS ACUMULADAS
{
	//echo $arrPath[2];
	if($arrPath[2]=='categories')//Ventas por supercategorias
	{

		$restId=$arrPath[3];
		$fechaInicio=$arrPath[4];
		$fechaFin=$arrPath[5];

		if($fechaInicio=='')
			$fechaInicio=date('Y-m-d 00:00:00');
		if($fechaFin=='')
			$fechaFin=date("Y-m-d 23:59:59");

		$api = new Api;
		$message=$api->getCategorySales($restId,$fechaInicio,$fechaFin);
		echo $message;
		
		

	}
	else if($arrPath[2]=='supercategories')//Ventas por supercategorias
	{

		$restId=$arrPath[3];
		$fechaInicio=$arrPath[4];
		$fechaFin=$arrPath[5];

		if($fechaInicio=='')
			$fechaInicio=date('Y-m-d 00:00:00');
		if($fechaFin=='')
			$fechaFin=date("Y-m-d 23:59:59");

		$api = new Api;
		$message=$api->getSupercategorySales($restId,$fechaInicio,$fechaFin);
		echo $message;
		
		

	}
	else if($arrPath[2]=='products')
	{
		//echo 'Top 10 Products';
		$restId=$arrPath[3];
		//echo 'RestId: '.$restId;					
		$fechaInicio=$arrPath[4];
		$fechaFin=$arrPath[5];

		if($fechaInicio=='')
			$fechaInicio=date('Y-m-d 00:00:00');
		if($fechaFin=='')
			$fechaFin=date("Y-m-d 23:59:59");

		$api = new Api;
		$message=$api->getProductSales($restId,$fechaInicio,$fechaFin);
		echo $message;
		
	}
	else if($arrPath[2]=='sales')
	{
		//echo 'Ventas x sucursal';
		
		$frequency='Daily'; //0-POR DIA, 1-POR SEMANA, 2-POR MES,4-POR ANO
		if($arrPath[3]!='')
			$frequency=$arrPath[3];
	
		try
		{
			/*
			$token=getBearerToken();
			$api= new JWTPropio;
				
			$payload= $api->decode($token,'test123',['HS256']);
				*/	
			//echo '<br>antes payload';
			//var_dump($payload);
			//echo 'desp payload';

			//echo '<br>FREQUency: '.$frequency;
					
			if($frequency=='Daily')//Diario
			{
				$api= new Api;
			
				//echo 'daily';
				$year=$arrPath[4];
				$month=$arrPath[5];
				$day=$arrPath[6];

				if($year=='')
					$year=date("Y");
				if($month=='')
					$month=date("m");
				if($day=='')
					$day=date("d");
						
				if($month=='January')
					$month=1;
				if($month=='February')
					$month=2;
				if($month=='March')
					$month=3;
				if($month=='April')
					$month=4;
				if($month=='May')
					$month=5;
				if($month=='June')
					$month=6;
				if($month=='July')
					$month=7;
				if($month=='August')
					$month=8;
				if($month=='September')
					$month=9;
				if($month=='October')
					$month=10;
				if($month=='November')
					$month=11;
				if($month=='December')
					$month=12;

				//echo 'antes';
				$message=$api->getBranchesDailySales($year,$month,$day);
				echo $message;
		

				
						
			}
			else if($frequency=='Weekly')//Semanal
			{
				$api= new Api;
				//echo 'Weekly';
				$year=$arrPath[4];
				$week=$arrPath[5];

				if($year=='')
					$year=date("Y");
				if($week=='')
					$week=date("W")-1;
						

						
				$message=$api->getBranchesWeeklySales($year,$week);
				echo $message;

						
			}
			else if($frequency=='Monthly')//Mensual
			{
				$api= new Api;
				$year=$arrPath[4];
				$month=$arrPath[5];
	
				if($year=='')
					$year=date("Y");
				if($month=='')
					$month=date("m");
						
				if($month=='January')
					$month=1;
				if($month=='February')
					$month=2;
				if($month=='March')
					$month=3;
				if($month=='April')
					$month=4;
				if($month=='May')
					$month=5;
				if($month=='June')
					$month=6;
				if($month=='July')
					$month=7;
				if($month=='August')
					$month=8;
				if($month=='September')
					$month=9;
				if($month=='October')
					$month=10;
				if($month=='November')
					$month=11;
				if($month=='December')
					$month=12;
							
						
				$message=$api->getBranchesMonthlySales($year,$month);
				echo $message;

						
				
						
			}
			else if($frequency=='Yearly')//Anual
			{
				$api= new Api;
				$year=$arrPath[4];
				if($year=='')
					$year=date('Y');

				$message=$api->getBranchesYearlySales($year);
				echo $message;
	
						
								

						
			}
					
		}
		catch(Exception $err)
		{
			echo '{"error":{"message":"'.$err->getMessage().'"}}';
			//echo '{"error:{"message":"'.$err->getMessage().'"}};
		}
	

		
	}
	else
	{
		
					
		$fechaInicio=$arrPath[2];
		$fechaFin=$arrPath[3];

		if($fechaInicio=='')
			$fechaInicio=date('Y-m-d 00:00:00');
		if($fechaFin=='')
			$fechaFin=date("Y-m-d 23:59:59");

		$api= new Api;
		$message=$api->getBranchesData($fechaInicio,$fechaFin);
		echo $message;
	
		
		
	}
}


function json_validate($string)
{
    // decode the JSON data
    $result = json_decode($string);

	$error='';
    // switch and check possible JSON errors
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = ''; // JSON is valid // No error has occurred
            break;
        case JSON_ERROR_DEPTH:
            $error = 'The maximum stack depth has been exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Invalid or malformed JSON.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Control character error, possibly incorrectly encoded.';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON.';
            break;
        // PHP >= 5.3.3
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_INF_OR_NAN:
            $error = 'One or more NAN or INF values in the value to be encoded.';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            $error = 'A value of a type that cannot be encoded was given.';
            break;
        default:
            $error = 'Unknown JSON error occured.';
            break;
    }

	/*
    if ($error !== '') {
        // throw the Exception or exit // or whatever :)
        exit($error);
    }*/

    // everything is OK
    return $error;
}

		function getBearerToken()
		{
			$headers=getAuthorizationHeader();
			
			$headers2 = getallheaders();
//echo 'headers2x:'. $headers2['Authorization'];
			
			//echo 'HEADERS: '. $headers;
			if(!empty($headers2['Authorization']))
			{
				if(preg_match('/Bearer\s(\S+)/',$headers2['Authorization'],$matches))
				{
					//echo 'matches'. $matches[1];
					return $matches[1];
				}
			}
			else
			{
				echo "Acces token not found: headers". $headers;
			}
		
		
		}
		
		function getAuthorizationHeader()
		{
			$headers = null;
		
		//var_dump($_SERVER);
			//$this->throwError(705,"Acces token not found: headers". $_SERVER["CONTENT_TYPE"]);
				
			//echo 'authorization: '. $_SERVER["AUTHORIZATION"];
			if(isset($_SERVER["AUTHORIZATION"]))
			{
				$headers= trim($_SERVER["AUTHORIZATION"]);
			}
			else if(isset($_SERVER["HTTP_AUTHORIZATION"]))
			{
				$headers= trim($_SERVER["HTTP_AUTHORIZATION"]);
			}
			else if(function_exists('apache_requests_headers'))
			{
				$requestHeaders= apache_request_headers();
				
				$requestHeaders = array_combine(array_map('ucwords',
				array_keys($requestHeaders)), array_values($requestHeaders));
				
				if(isset($requestHeaders["Authorization"]))
				{
					$headers= trim($requestHeaders["Authorization"]);
				}
			}
			
			return $headers;
		}
		


			

?>
