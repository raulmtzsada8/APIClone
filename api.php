<?php

require_once('jwtpropio.php');
require_once('ExpiredException.php');

		
class Api 
{
	private $servername = "pom";
	private $usernameServer = "r";
	private $password = "1!";

	public function __construct()
	{
			
	}
	
	/////////////////////////////LOGIN/////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	public function login() 
	{
		$handler =  file_get_contents('php://input');
		$request = $handler;

		$errorJson=json_validate($request);
		
		if($errorJson!='')
		{
			$error='{"error":{"message":"'.$errorJson.'"}}';
			echo $error;
			exit;
		}
		
		$objUsuario=json_decode($request);
				
		if($objUsuario->session->username=='')
		{
			$error='{"error":{"message":"No se pudo procesar objeto de usuario"}}';
			return $error;
		
		}
		else
		{
		
		
			//echo 'Si se pudo procesar objeto de usuario';
			$username=$objUsuario->session->username;
			$passw=$objUsuario->session->password;
			
			
			
			$result='';

			$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
			$mysqli->query("SET NAMES 'utf8'");

			$sql="select id,username,active,cadenaId from usersWeb  ";
			$sql=$sql .' where username="'.$username.'" and password="'.$passw.'"'; 
			
			
			$rs = $mysqli->query($sql);//('select * from insumos where cadenaId='.$cadenaId);
			$result = array();
			if($row = mysqli_fetch_object($rs))
			{
				array_push($result, $row);
			}
			
			if(!is_array($result) || count($result)==0)
			{
				$error='{"error":{"message":"Invalid username or password"}}';
				return $error;
		
			}
						//echo 'desp';

			//echo 'ACTIVO: '. $result[0]->active.'|';
			if($result[0]->active!=1)
			{
				$error='{"error":{"message":"User is not active"}}';
				return $error;
		
				
			}
			
			$userId=$result[0]->id;
			$payLoad=[
				'iat'=>time(),
				'iss'=>'localhost',
				'exp'=>time() + (3600),
				'userId'=>$result[0]->id,
				'cadenaId'=>$result[0]->cadenaId,
			];
			$secretKey='test123';
			
			$jwt= new JWTPropio;
			
				
			try
			{
				
				
				
				$token=$jwt->encode($payLoad,$secretKey,'HS256');
				$data='{"sesion":{"token":"'.$token.'"}}';

				
				$sql="update usersWeb set loggedIn=1";
				$sql=$sql .' where id='.$userId; 
				$mysqli->query($sql);//('select * from insumos where cadenaId='.$cadenaId);
						
				
				return $data;
				
			}
			catch(Exception $err)
			{
				$error='{"error":{"Error de jwt:'.$err->getMessage.'"}}';
				return $error;
		
				//return 'Error de jwt:'.$err->getMessage;
			}
			

			
		}
		
	}
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////LOGOUT////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////
	public function logout()
	{
				
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';
			//exit;
					//echo '{"error:{"message":"'.$err->getMessage().'"}};
		}
		
		$data='{"sesion":{"message":"Log Out successful"}}';
		
		$userId=$payload->userId;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");
		
		$sql="update usersWeb set loggedIn=0";
		$sql=$sql .' where id='.$userId;
		//echo $sql;		
		$mysqli->query($sql);//('select * from insumos where cadenaId='.$cadenaId);
				
		return $data;

	}

	////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////DAILY SALES POR CADENA DIARIAS/////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	public function getDailySales($year,$month,$day)
	{
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';
			//exit;
					//echo '{"error:{"message":"'.$err->getMessage().'"}};
		}
		

		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		$userId=$payload->userId;
		
		$sql="select id,ifnull(loggedIn,0) as loggedIn from usersWeb  ";
		$sql=$sql .' where id='.$userId; 
		//echo $sql;
		$result = $mysqli->query($sql);
		if($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			if($row["loggedIn"]==0)
			{
				return '{"error":{"message":"user not logged in"}}';
			}
		}			
		
		$query="select DATE_FORMAT( createTime, '%Y-%m-%d %H:%i:%s') as fecha from bills where restId=37 order by id asc limit 1";				
						
		$result = $mysqli->query($query);
		if($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$fechaInicial=$row["fecha"];
		}
						
		//echo 'fechaInicial:'.$fechaInicial;
						
						
		$query="select datediff(NOW(), '".$fechaInicial."') as dias";
						
		$result = $mysqli->query($query);
		if($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$diasAtras=$row["dias"];
		}

		//echo 'Dias Atras: '.$diasAtras;

		//echo 'DIARIO';
						
		//$query="select date_format(createTime,'%M') as mes,date_format(createTime,'%Y') as ano,date_format(createTime,'%d') as dia,";
		$query="select date_format(createTime,'%Y-%m-%dT%H:%i:%sZ') as created_at,";
		$query=$query." sum(total) as 'amount'";
		$query=$query." from bills ";  
		$query=$query." where restId=37";
						
		if($year!='' && $month!='' && $day!='')
			$query=$query." and year(createTime)=".$year." and month(createTime)=".$month." and day(createTime)=".$day;
		$query=$query." group by year(createTime), month(createTime),day(createTime),hour(createTime)";  
		$query=$query." order by year(createTime) desc, month(createTime) desc,day(createTime) desc,hour(createTime)  limit 50";
						
		//echo $query .'<br>';
		$result = $mysqli->query($query);


		$sumaTotal=0.00;
		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$sumaTotal=$sumaTotal + $row["amount"];
			$arrDatos[]=$row;

		}
	
								
		//echo $query;
		$message='{"sales":[';
		foreach($arrDatos as $row)
		{
			$message=$message.'{"created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
		}
						
		$message=substr($message,0,strlen($message)-1);				
		$message=$message.'],';
		$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
		return $message;
									
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////DAILY SALES POR CADENA SEMANALES/////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	public function getWeeklySales($year,$week)
	{
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';
			//exit;
					//echo '{"error:{"message":"'.$err->getMessage().'"}};
		}
		

		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query="select DATE_FORMAT(createTime,'%Y-%m-%dT%H:%i:%sZ') as created_at,";
		$query=$query." sum(total) as 'amount'";
		$query=$query." from bills ";  
		$query=$query." where restId=37"; 
		$query=$query." and year(createTime)=".$year. ' and week(createTime)='.$week; 
						
						
		$query=$query." group by year(createTime), month(createTime),day(createTime)";  
		$query=$query." order by year(createTime) desc, month(createTime) desc,day(createTime)desc  limit 50";;
		//echo $query .'<br>';
		$result = $mysqli->query($query);


		$sumaTotal=0.00;
		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$sumaTotal=$sumaTotal + $row["amount"];
			$arrDatos[]=$row;

		}
					
		$message='{"sales":[';
		foreach($arrDatos as $row)
		{
			$message=$message.'{"created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
		}
						
		$message=substr($message,0,strlen($message)-1);
						
		$message=$message.'],';
		$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
		return $message;
													
	}
	
	//////////////////////////////////////////////////////////////////////////////////
	/////////////////////DAILY SALES POR CADENA MENSUALES/////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////
	public function getMonthlySales($year,$month)
	{
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';
			//exit;
					//echo '{"error:{"message":"'.$err->getMessage().'"}};
		}
		

		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query="select date_format(createTime,'%Y-%m-%dT%H:%i:%sZ') as created_at,";     
		$query=$query." sum(total) as 'amount'";
		$query=$query." from bills";  
		$query=$query." where restId=37"; 
		if($year!='' && $month!='')
			$query=$query." and year(createTime)=".$year."  and month(createTime)=".$month;
						
		$query=$query." group by year(createTime), month(createTime),day(createTime) "; 
		$query=$query."order by year(createTime) desc, month(createTime),day(createTime) desc limit 50 ";
						
		$result = $mysqli->query($query);


		$sumaTotal=0.00;
		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$sumaTotal=$sumaTotal + $row["amount"];
			$arrDatos[]=$row;
		}
	
		$message='{"sales":[';
		foreach($arrDatos as $row)
		{
			$message=$message.'{"created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
		}
						
		$message=substr($message,0,strlen($message)-1);
						
		$message=$message.'],';
		$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
		return $message;													
	}
	
	///////////////////////////////////////////////////////////////////////
	/////////////////////////DAILY SALES POR CADENA ANUALES////////////////
	/////////////////////////////////////////////////////////////////////////
	public function getYearlySales($year)
	{
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';
			//exit;
					//echo '{"error:{"message":"'.$err->getMessage().'"}};
		}
		

		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query="select date_format(createTime,'%Y-%m-%dT%H%i%sZ') as created_at,";     
		$query=$query." sum(total) as 'amount'";
		$query=$query." from bills ";  
		$query=$query." where restId=37"; 
		if($fechaInicio!='')
			$query=$query." and createTime>='". $fechaInicio."'"; 
		if($fechaFin!='')
			$query=$query." and createTime<='". $fechaFin."'"; 
		if($year!='')
			$query=$query." and year(createTime)=". $year.""; 
							
								
		$query=$query." group by year(createTime),month(createTime) "; 
		$query=$query." order by year(createTime) desc,month(createTime);";

		$result = $mysqli->query($query);


		$sumaTotal=0.00;
		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$sumaTotal=$sumaTotal + $row["amount"];
			$arrDatos[]=$row;

		}
	
								
	
		$arrDatosEnviar=  array();
		//$arrDatosSuma="total_sales"=>"$sumaTotal";
		//$arrDatosEnviar=["sales"=>$arrDatos,"meta"=>"$sumaTotal"];
		//$message=json_encode($arrDatosEnviar);
		$message='{"sales":[';
		foreach($arrDatos as $row)
		{
			$message=$message.'{"created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
		}
						
		$message=substr($message,0,strlen($message)-1);
						
		$message=$message.'],';
		$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
		return $message;
																			
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////VENTAS POR SUPAERCATEGORIAS///////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	public function getSupercategorySales($restId,$fechaInicio,$fechaFin)
	{
		if(!is_numeric($restId))
		{
			$message='{"error":"message":"restId is not valid"';
			return $message;	
		}
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';
			
		}
					

		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		$query=" select ifnull(sum(ord.quantity*ord.price),0.00) as Monto";
		$query=$query." from orders ord";
		$query=$query." left join bills bill on(ord.billId=bill.id and ord.restId=bill.restId)";
		$query=$query." where   ord.statusId!=4";
		$query=$query." and bill.restId=".$restId;
		$query=$query." and bill.statusId!=4"; 
		$query=$query." and ord.productId not in(9998,9999,9997,9996)"; 
		$query=$query." and ord.createTime between '".$fechaInicio."' and '".$fechaFin."' "; 
		$result = $mysqli->query($query);

		if ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$sumaTotal=$row["Monto"];
		}
		
		$query="SELECT sc.id as vId, sc.name as Supercategoría, sum(o.quantity*o.price*o.price) as Monto,sum(o.quantity) as quantity,0 as Porcentaje FROM orders o join bills b on(o.billId=b.id and o.restId=b.restId) ";
		$query=$query." join products p on(o.productId=p.id and o.restId=p.restId) join categories c on(c.id = p.categoryId and c.restId = p.restId) ";
		$query=$query."  left join supercategories sc on(sc.id=c.superCategoryId and sc.restId=c.restId) WHERE o.statusId!=4 and b.statusId=2 and b.restId =".$restId;
		$query=$query."  and o.createTime between '".$fechaInicio."' and '".$fechaFin."' GROUP BY sc.id ORDER BY Monto desc ";
		
		//echo $query;
		$result = $mysqli->query($query);

		//$sumaTotal=0.00;
		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			//$sumaTotal=$sumaTotal + $row["Monto"];
			$prc=round(($row["Monto"]/$sumaTotal)*100,2);
			$row["Porcentaje"]=$prc;
			$arrDatos[]=$row;
		}
		
		$message='{"products":[';
		foreach($arrDatos as $row)
		{
			//echo $rowDatos["Producto"].' '. $rowDatos["Porcentaje"];
				$message=$message.'{"supercategory_name":"'.$row["Supercategoría"].'","total_sales":'.$row["Monto"].',"quantity":'.$row["quantity"].',"percentage_total_sales":'.$row["Porcentaje"].',"unit_price":'.$row["price"].'},';
		}
						
		$message=substr($message,0,strlen($message)-1);		
		$message=$message.']}';
		//$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
		return $message;

		
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////VENTAS POR PRODUCTO/////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////
	public function getProductSales($restId,$fechaInicio,$fechaFin)
	{
		if(!is_numeric($restId))
		{
			$message='{"error":"message":"restId is not valid"';
			return $message;	
		}
		
		if(!is_numeric($restId))
		{
			$message='{"error":"message":"restId is not valid"';
			echo $message;
			exit;
			
		}
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';	
		}

		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query=" select ifnull(sum(ord.quantity*ord.price),0.00) as Monto";
		$query=$query." from orders ord";
		$query=$query." left join bills bill on(ord.billId=bill.id and ord.restId=bill.restId)";
		$query=$query." where   ord.statusId!=4";
		$query=$query." and bill.restId=".$restId;
		$query=$query." and bill.statusId!=4"; 
		$query=$query." and ord.productId not in(9998,9999,9997,9996)"; 
		$query=$query." and ord.createTime between '".$fechaInicio."' and '".$fechaFin."' "; 
		$result = $mysqli->query($query);

		if ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$sumaTotal=$row["Monto"];
		}
		
		
		$query=" select  ct.name as catName,ord.productName as Producto,";
		$query=$query." ifnull(sum(ord.quantity*ord.price),0.00) as Monto,0.00 as Porcentaje,ord.price,sum(ord.quantity) as quantity";
		$query=$query." from orders ord";
		$query=$query." left join bills bill on(ord.billId=bill.id and ord.restId=bill.restId)";
		$query=$query." left join products prod on(ord.productId=prod.id and ord.restId=prod.restId)";
		$query=$query." left join categories ct on(prod.categoryId=ct.id and prod.restId=ct.restId )";
		$query=$query." where   ord.statusId!=4";
		$query=$query." and bill.restId=".$restId;
		$query=$query." and bill.statusId!=4"; 
		$query=$query." and ord.productId not in(9998,9999,9997,9996)"; 
		$query=$query." and ord.createTime between '".$fechaInicio."' and '".$fechaFin."' "; 
		$query=$query." group by ord.productName order by Monto desc limit 10";
		
		//echo $query;
		$result = $mysqli->query($query);

		//$sumaTotal=0.00;
		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			//$sumaTotal=$sumaTotal + $row["Monto"];
			$prc=round(($row["Monto"]/$sumaTotal)*100,2);
			$row["Porcentaje"]=$prc;
			$arrDatos[]=$row;
		}
		
		$message='{"products":[';
		foreach($arrDatos as $row)
		{
			//echo $rowDatos["Producto"].' '. $rowDatos["Porcentaje"];
			$message=$message.'{"category_name":"'.$row["catName"].'","name":"'.$row["Producto"].'","total_sales":'.$row["Monto"].',"quantity":'.$row["quantity"].',"percentage_total_sales":'.$row["Porcentaje"].',"unit_price":'.$row["price"].'},';
		}
						
		$message=substr($message,0,strlen($message)-1);		
		$message=$message.']}';
		//$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
		return $message;

		
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////VENTAS POR PRODUCTO/////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////
	public function getCategorySales($restId,$fechaInicio,$fechaFin)
	{
		if(!is_numeric($restId))
		{
			$message='{"error":"message":"restId is not valid"';
			return $message;	
		}
		
		if(!is_numeric($restId))
		{
			$message='{"error":"message":"restId is not valid"';
			echo $message;
			exit;
			
		}
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';	
		}

		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query=" select ifnull(sum(ord.quantity*ord.price),0.00) as Monto";
		$query=$query." from orders ord";
		$query=$query." left join bills bill on(ord.billId=bill.id and ord.restId=bill.restId)";
		$query=$query." where   ord.statusId!=4";
		$query=$query." and bill.restId=".$restId;
		$query=$query." and bill.statusId!=4"; 
		$query=$query." and ord.productId not in(9998,9999,9997,9996)"; 
		$query=$query." and ord.createTime between '".$fechaInicio."' and '".$fechaFin."' "; 
		$result = $mysqli->query($query);

		if ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$sumaTotal=$row["Monto"];
		}
		
		
		$query=" select  ct.name as catName,";
		$query=$query." ifnull(sum(ord.quantity*ord.price),0.00) as Monto,0.00 as Porcentaje,ord.price,sum(ord.quantity) as quantity";
		$query=$query." from orders ord";
		$query=$query." left join bills bill on(ord.billId=bill.id and ord.restId=bill.restId)";
		$query=$query." left join products prod on(ord.productId=prod.id and ord.restId=prod.restId)";
		$query=$query." left join categories ct on(prod.categoryId=ct.id and prod.restId=ct.restId )";
		$query=$query." where   ord.statusId!=4";
		$query=$query." and bill.restId=".$restId;
		$query=$query." and bill.statusId!=4"; 
		$query=$query." and ord.productId not in(9998,9999,9997,9996)"; 
		$query=$query." and ord.createTime between '".$fechaInicio."' and '".$fechaFin."' "; 
		$query=$query." group by ct.name order by Monto desc limit 10";
		
		//echo $query;
		$result = $mysqli->query($query);

		//$sumaTotal=0.00;
		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			//$sumaTotal=$sumaTotal + $row["Monto"];
			$prc=round(($row["Monto"]/$sumaTotal)*100,2);
			$row["Porcentaje"]=$prc;
			$arrDatos[]=$row;
		}
		
		$message='{"categories":[';
		foreach($arrDatos as $row)
		{
			//echo $rowDatos["Producto"].' '. $rowDatos["Porcentaje"];
			$message=$message.'{"category_name":"'.$row["catName"].'","total_sales":'.$row["Monto"].',"quantity":'.$row["quantity"].',"percentage_total_sales":'.$row["Porcentaje"].',"unit_price":'.$row["price"].'},';
		}
						
		$message=substr($message,0,strlen($message)-1);		
		$message=$message.']}';
		//$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
		return $message;

		
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////VENTAS POR SUCURSALES DIARIAS////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	public function getBranchesDailySales($year,$month,$day)
	{
		
		//echo 'paso 0';
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';	
		}
		
		//		echo 'paso 1';


		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query="select DATE_FORMAT( createTime, '%Y-%m-%d %H:%i:%s') as fecha from bills where restId=37 order by id asc limit 1";
				//echo $query;
						
				$result = $mysqli->query($query);
				if($row = $result->fetch_array(MYSQLI_ASSOC)) 
				{
					$fechaInicial=$row["fecha"];
				}
						
				//echo 'fechaInicial:'.$fechaInicial;
						
						
				$query="select datediff(NOW(), '".$fechaInicial."') as dias";
						
				$result = $mysqli->query($query);
				if($row = $result->fetch_array(MYSQLI_ASSOC)) 
				{
					$diasAtras=$row["dias"];
				}

				//echo 'Dias Atras: '.$diasAtras;

				//echo 'DIARIO';
						
				//$query="select date_format(createTime,'%M') as mes,date_format(createTime,'%Y') as ano,date_format(createTime,'%d') as dia,";
				$query="select b.restId,cli.nombre,cli.sucursal,date_format(createTime,'%Y-%m-%dT%H:%i:%sZ') as created_at,";
				$query=$query." sum(total) as 'amount'";
				$query=$query." from bills b, s3menudt.clientes cli";   
				$query=$query." where b.restId=cli.restaurant_id and b.restId in(37,143,241)";
				
				if($year!='' && $month!='' && $day!='')
					$query=$query." and year(createTime)=".$year." and month(createTime)=".$month." and day(createTime)=".$day;
				$query=$query." group by b.restId, year(createTime), month(createTime),day(createTime),hour(createTime)";  
				$query=$query." order by year(createTime) desc, month(createTime) desc,day(createTime) desc,hour(createTime)  limit 50";
				
				//echo $query;
				
				$result = $mysqli->query($query);


				$sumaTotal=0.00;
				$arrDatos=  array();
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
				{
					$sumaTotal=$sumaTotal + $row["amount"];
					$arrDatos[]=$row;
				}
	
								
				$message='{"sales":[';
				foreach($arrDatos as $row)
				{
					$message=$message.'{"restId":'.$row["restId"].',"restName":"'.$row["nombre"].' '.$row["sucursal"].'","created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
				}
						
				$message=substr($message,0,strlen($message)-1);		
				$message=$message.'],';
				$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
				
				
				return $message;

		
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////VENTAS POR SUCURSALES SEMANAL////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	public function getBranchesWeeklySales($year,$week)
	{
		
		//echo 'paso 0';
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';	
		}
		
		//		echo 'paso 1';


		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
						//echo 'SEMANAL';
				$query="select b.restId,cli.nombre,cli.sucursal,DATE_FORMAT(createTime,'%Y-%m-%dT%H:%i:%sZ') as created_at,";
				$query=$query." sum(total) as 'amount'";
				$query=$query." from bills b, s3menudt.clientes cli";   
				$query=$query." where b.restId=cli.restaurant_id and b.restId in(37,143,241)";
				$query=$query." and year(createTime)=".$year. ' and week(createTime)='.$week; 
						
						
				$query=$query." group by restId,year(createTime), month(createTime),day(createTime)";  
				$query=$query." order by year(createTime) desc, month(createTime) desc,day(createTime)desc  limit 50";;
				//echo $query .'<br>';
				$result = $mysqli->query($query);

				$sumaTotal=0.00;
				$arrDatos=  array();
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
				{
					$sumaTotal=$sumaTotal + $row["amount"];
					$arrDatos[]=$row;
				}
	
								
						
				$message='{"sales":[';
				foreach($arrDatos as $row)
				{
					$message=$message.'{"restId":'.$row["restId"].',"restName":"'.$row["nombre"].' '.$row["sucursal"].'"created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
				}
						
				$message=substr($message,0,strlen($message)-1);
						
				$message=$message.'],';
				$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
				return $message;

		
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////VENTAS POR SUCURSALES MENSUAL////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	public function getBranchesMonthlySales($year,$month)
	{
		
		//echo 'paso 0';
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';	
		}
		
		//		echo 'paso 1';


		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query="select b.restId,cli.nombre,cli.sucursal,date_format(createTime,'%Y-%m-%dT%H:%i:%sZ') as created_at,";     
				$query=$query." sum(total) as 'amount'";
				$query=$query." from bills b, s3menudt.clientes cli";   
				$query=$query." where b.restId=cli.restaurant_id and b.restId in(37,143,241)";
				if($year!='' && $month!='')
					$query=$query." and year(createTime)=".$year."  and month(createTime)=".$month;
						
				$query=$query." group by b.restId,year(createTime), month(createTime),day(createTime) "; 
				$query=$query."order by year(createTime) desc, month(createTime),day(createTime) desc limit 50 ";
						
				//echo $query .'<br>';
				$result = $mysqli->query($query);


				$sumaTotal=0.00;
				$arrDatos=  array();
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
				{
					$sumaTotal=$sumaTotal + $row["amount"];
					$arrDatos[]=$row;

				}
	
								
				/*
				$arrDatosEnviar=  array();
				$arrDatosEnviar=["sales"=>$arrDatos,"meta"=>"$sumaTotal"];
				$message=json_encode($arrDatosEnviar);
				echo $message;
*/
				$message='{"sales":[';
				foreach($arrDatos as $row)
				{
					$message=$message.'{"restId":'.$row["restId"].',"restName":"'.$row["nombre"].' '.$row["sucursal"].'"created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
				}
						
				$message=substr($message,0,strlen($message)-1);
						
				$message=$message.'],';
				$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
				return $message;
		
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////VENTAS POR SUCURSALES ANUAL////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	public function getBranchesYearlySales($year)
	{
		
		//echo 'paso 0';
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';	
		}
		
		//		echo 'paso 1';


		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		$query="select b.restId,cli.nombre,cli.sucursal,date_format(createTime,'%Y-%m-%dT%H%i%sZ') as created_at,";     
				$query=$query." sum(total) as 'amount'";
				$query=$query." from bills b, s3menudt.clientes cli";   
				$query=$query." where b.restId=cli.restaurant_id and b.restId in(37,143,241)";
				if($fechaInicio!='')
					$query=$query." and createTime>='". $fechaInicio."'"; 
				if($fechaFin!='')
					$query=$query." and createTime<='". $fechaFin."'"; 
				if($year!='')
					$query=$query." and year(createTime)=". $year.""; 
							
								
				$query=$query." group by b.restId,year(createTime),month(createTime) "; 
				$query=$query." order by year(createTime) desc,month(createTime);";

				//echo $query;
								
				$result = $mysqli->query($query);


				$sumaTotal=0.00;
				$arrDatos=  array();
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
				{
					$sumaTotal=$sumaTotal + $row["amount"];
					$arrDatos[]=$row;

				}
	
								
	
				$arrDatosEnviar=  array();
				//$arrDatosSuma="total_sales"=>"$sumaTotal";
				//$arrDatosEnviar=["sales"=>$arrDatos,"meta"=>"$sumaTotal"];
				//$message=json_encode($arrDatosEnviar);
				$message='{"sales":[';
				foreach($arrDatos as $row)
				{
					$message=$message.'{"restId":'.$row["restId"].',"restName":"'.$row["nombre"].' '.$row["sucursal"].'"created_at":"'.$row["created_at"].'","amount":'.$row["amount"].'},';
				}
						
				$message=substr($message,0,strlen($message)-1);
						
				$message=$message.'],';
				$message=$message.'"meta":{"total_sales":'.$sumaTotal.'}}';
				return $message;
				
	}
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////DATOS DE SUCURSALES////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	public function getBranchesData($fechaInicio,$fechaFin)
	{
		
		//echo 'paso 0';
		
		try{
		
			$token=getBearerToken();
			$api= new JWTPropio;
			$payload= $api->decode($token,'test123',['HS256']);
				
		}
		catch(Exception $err)
		{
			return '{"error":{"message":"'.$err->getMessage().'"}}';	
		}
		
		//		echo 'paso 1';


		$sumaTotal=0.00;
		
		$mysqli = new mysqli($this->servername, $this->usernameServer, $this->password, "parrotweb");
		$mysqli->query("SET NAMES 'utf8'");

		
		//echo 'fechaInicio:'.$fechaInicio;
		//echo 'fechaFin:'.$fechaFin;
		
		//echo 'Sacamos Datos de sucursales';
	
		$query="select restaurant_id,nombre,sucursal from s3menudt.clientes where IdCadena=4";
		$result = $mysqli->query($query);


		$arrDatos=  array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) 
		{
			$arrDatos[]=$row;
					
		}

		//var_dump($arrDatos);
		$jsonDatos='{"branches":[';
		foreach($arrDatos as $rowDatos)
		{
			$jsonDatos=$jsonDatos.'{"restId":'.$rowDatos["restaurant_id"].',"name":"'.$rowDatos["nombre"]. ' ' . $rowDatos["sucursal"].'",' ;
		
			$query="select sum(total) as total from bills where restId=".$rowDatos["restaurant_id"]. " and createTime between '".$fechaInicio."' and '".$fechaFin."' and statusId!=4";
			$result = $mysqli->query($query);
			if($row = $result->fetch_array(MYSQLI_ASSOC)) 
			{
				$ventasTotales=$row["total"];
				if($ventasTotales=='')
					$ventasTotales=0.00;
				
				//echo 'Ventas Totales:'.$ventasTotales;
				$jsonDatos=$jsonDatos.'"totalSales":'.$ventasTotales.',';
			}
			
			$query="select count(id) as cuenta,sum(total) as total from bills where restId=".$rowDatos["restaurant_id"]. " and createTime between '".$fechaInicio."' and '".$fechaFin."' and statusId=2";
			//echo $query;
			$result = $mysqli->query($query);
			if($row = $result->fetch_array(MYSQLI_ASSOC)) 
			{
				$ventasCerradas=$row["total"];
				$cuentasCerradas=$row["cuenta"];
				if($ventasCerradas=='')
					$ventasCerradas=0.00;
				if($cuentasCerradas=='')
					$cuentasCerradas=0;
				
				//echo 'Ventas Cuentas Cerradas:'.$ventasCerradas;
				//echo 'Cuentas Cerradas:'.$cuentasCerradas;
			
				$jsonDatos=$jsonDatos.'"closedTickets":{"size":'.$cuentasCerradas.',"total":'.$ventasCerradas.'},';
			
			}
			
			$query="select count(id) as cuenta,sum(total) as total from bills where restId=".$rowDatos["restaurant_id"]. " and createTime between '".$fechaInicio."' and '".$fechaFin."' and statusId=1";
			//echo $query;
			$result = $mysqli->query($query);
			if($row = $result->fetch_array(MYSQLI_ASSOC)) 
			{
				$ventasAbiertas=$row["total"];
				$cuentasAbiertas=$row["cuenta"];
				if($ventasAbiertas=='')
					$ventasAbiertas=0.00;
				if($cuentasAbiertas=='')
					$cuentasAbiertas=0;
				
					//echo 'Ventas Cuentas Abiertas:'.$ventasAbiertas;
					//echo 'Cuentas Abiertas:'.$cuentasAbiertas;
				$jsonDatos=$jsonDatos.'"openAccounts":{"size":'.$cuentasAbiertas.',"total":'.$ventasAbiertas.'},';
			
			}

			$totalCuentas=$cuentasAbiertas + $cuentasCerradas;
			
			if($totalCuentas>0)
				$averageTicket=$ventasTotales/$totalCuentas;
			else
				$averageTicket=0;
			
			$jsonDatos=$jsonDatos.'"averageTicket":'.$averageTicket.'},';
		}
		
		$jsonDatos=substr ( $jsonDatos , 0 , strlen($jsonDatos)-1 );
		$jsonDatos=$jsonDatos.']}';
		return $jsonDatos;
				
	}
	
	
	private function json_validate($string)
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

	
		return $error;
	}

	function getBearerToken()
	{
		$headers=getAuthorizationHeader();	
		$headers2 = getallheaders();
			
		if(!empty($headers2['Authorization']))
		{
			if(preg_match('/Bearer\s(\S+)/',$headers2['Authorization'],$matches))
			{
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
			
		//echo 'HEADERS:'.$headers;
			
		return $headers;
	}

		
}

?>
