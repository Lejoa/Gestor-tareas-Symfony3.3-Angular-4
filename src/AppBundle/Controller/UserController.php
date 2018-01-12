<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\User;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

class UserController extends Controller
{
	public function newAction(Request $request)
	{
		$helpers = $this->get(Helpers::class);

		#Recibimos la variable que nos llega por POST
		$json = $request->get("json",null);
		#En ese instante se recibe el JSON como una cadena de String
		
		/*Se utiliza esta variable para acceder a las
		 propiedades del JSON
		 El json_decode hace la conversión del string
		 recibido en la variable post a un objeto PHP*/
		$params = json_decode($json);
 		
		$data = array(
			'status'=>'error',
			'code'=> 400,
			'msg' => 'User not created');

		#El metodo json nos devuelve el array
		#en un objeto JSON

		if($json != null){

			$createdAt = new \Datetime("now");
			$role = 'user';

			#Se verifica si existe la información y se asigna 
			#a la variable
			$email = (isset($params->email))?$params -> email: null;
			$name = (isset($params->name))? $params->name:null;
			$surname = (isset($params->surname))? $params->surname:null;
			$password = (isset($params->password))? $params->password:null;

			#Verificación de email
			$emailConstraint = new Assert\Email();
			$emailConstraint->message = "This email is not valid!!";
			#Se accede al servicio de validación y utiliza el metodo validate
			$validate_email = $this->get("validator")->validate($email,
			 $emailConstraint);

			if($email != null && count($validate_email)==0  && 
				$password != null && $name != null && $surname != null){

				$user = new User();
				$user->setName($name);
				$user->setSurname($surname);
				$user->setRole($role);
				$user->setEmail($email);

				/* Cifrar la contrasñe*/

				$pwd = hash('sha256',$password);
				$user->setPassword($pwd);

				$em = $this->getDoctrine()->getManager();
				$isset_user = $em->getRepository('BackendBundle:User')->findBy(array("email"=> $email
					));

				if(count($isset_user)== 0){

					$em->persist($user);
					$em->flush();

					$data = array('status'=>'Succes',
					'code'=> 200,
					'msg' => 'User  created',
					'user'=>$user
					);

				}else{

					$data = array(
					'status'=>'error',
					'code'=> 400,
					'msg' => 'Duplicated user');
				}

			}

		} 

		return $helpers ->json($data);
	}

	public function editAction(Request $request)
	{
		
		$helpers = $this->get(Helpers::class);

		/*Comprobar que el token llegado por POST
		es correcto, usando nuestro servicio de
		autenticación*/

		$jwt_auth = $this->get(JwtAuth::class);

		/*Recogemos la variable llegada por POST
		llamada "authorization"*/
		$token = $request->get('authorization',null);

		$checkToken = $jwt_auth->checkToken($token,true);

		if($checkToken){

			$em = $this->getDoctrine()->getManager();

			/*Obtenemos la información del usuario logueado*/
			$identity = $jwt_auth->checkToken($token,true);

			$user = $em->getRepository('BackendBundle:User')->findOneBy(array(
					'id'=>$identity->sub
					));

			#Recibimos la variable que nos llega por POST
			$json = $request->get("json",null);
			#En ese instante se recibe el JSON como una cadena de String
			
			/*Se utiliza esta variable para acceder a las
			 propiedades del JSON
			 El json_decode hace la conversión del string
			 recibido en la variable post a un objeto PHP*/
			$params = json_decode($json);
	 		
			$data = array(
				'status'=>'error',
				'code'=> 400,
				'msg' => 'User not update');


			if($json != null){

				$createdAt = new \Datetime("now");
				$role = 'user';

				#Se verifica si existe la información y se asigna 
				#a la variable
				$email = (isset($params->email))?$params -> email: null;
				$name = (isset($params->name))? $params->name:null;
				$surname = (isset($params->surname))? $params->surname:null;
				$password = (isset($params->password))? $params->password:null;

				#Verificación de email
				$emailConstraint = new Assert\Email();
				$emailConstraint->message = "This email is not valid!!";
				#Se accede al servicio de validación y utiliza el metodo validate
				$validate_email = $this->get("validator")->validate($email,
				 $emailConstraint);

				if($email != null && count($validate_email)==0  && 
					$password != null && $name != null && $surname != null){

					
					$user->setName($name);
					$user->setSurname($surname);
					$user->setRole($role);
					$user->setEmail($email);

					if($password != null){

						$pwd = hash('sha256',$password);
						$user->setPassword($pwd);

					}

					$em = $this->getDoctrine()->getManager();

					$isset_user = $em->getRepository('BackendBundle:User')->findBy(array("email"=> $email
						));

					if(count($isset_user) == 0 || $identity->email == $email){

						$em->persist($user);
						$em->flush();

						$data = array('status'=>'Succes',
						'code'=> 200,
						'msg' => 'User update',
						'user'=>$user
						);

					}else{

						$data = array(
						'status'=>'error',
						'code'=> 400,
						'msg' => 'User not update'
						);
					}
				}
			} 
		}else {

				$data = array(
						'status'=>'error',
						'code'=> 400,
						'msg' => 'Authorization no valid');
					}

			return $helpers ->json($data);

			}
}