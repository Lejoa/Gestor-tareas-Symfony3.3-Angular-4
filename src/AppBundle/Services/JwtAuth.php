<?php

namespace AppBundle \Services;

use Firebase\JWT\JWT;

Class JwtAuth
{
	public $manager;
	public $key;

	public function __construct($manager)
	{
		$this->manager = $manager;
		$this->key = '$3cretKey';
	}

	public function signup($email,$password,$hash = null)
	{
		$user = $this->manager->getRepository('BackendBundle:User')->findOneBy(
			array("email"=>$email,
					"password"=>$password));

		$signup = false;

		if(is_object($user)){
				$signup = true;		
		}

		if($signup){

			$token = array(
				"sub"=>$user ->getId(),
				"email" => $user->getEmail(),
				"name" => $user->getName(),
				"surname" => $user->getSurname(),
				"iat"=>time(),
				"exp"=>time()+(7*24*60*60)
				);

			$jwt = JWT::encode($token,$this->key,'HS256');
			$decoded = JWT::decode($jwt,$this->key,array('HS256'));
			$data = $jwt;

			/*$data = array(
					'status' => 'succes',
					'user' => $user
					);
			*/
				if($hash == null){
					
					$data = $jwt;
				
				}else{
					$data = $decoded;
				}

		}else{

			$data = array(
					'status' => 'error',
					'user' => 'Fail LOgin'
					);
		}

		return $data;
	}

	public function checktoken($jwt,$identity=false)
    {
        #Debemos decodificar nuestro Token
        #Pasamos nuestro Token, la clave secreta para dcodificar y 
        #una array con el metodo de cifrado
    	$auth = false;
    	try{

        $decoded = JWT::decode($jwt,$this->key,array('HS256'));
        
        }catch(\UnexpectedValueException  $e){
        	$auth = false;
        }catch(\DomainException $e){
        	$auth = false;
        }
        #El metodo sub es para sacar el id del objeto
        #Se comprueba que decoded sea un objeto correcto
        #de usuario
        if(isset($decoded) && is_object($decoded) && isset($decoded->sub)){
        	$auth=true;
        }

        if($identity==false){
        	return $auth;
        }else{
        	return $decoded;
        }
    }

}