<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

class DefaultController extends Controller
{
   
    
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    #Los metodos deben poseer Action para que sean publicos como ruta, si no lo tienen los tratara como metodos internos para el uso de la clase
    public function loginAction(Request $request)
    {
        #Cargamos el servicio de helpers
       $helpers = $this->get(Helpers::Class);
       #Recibimos la información JSON por POST
       $json = $request->get('json',null);

       $data = array(
            'status'=> 'error',
            'data'=>'Send JSON via post'
        );

       if($json !=null){

            #Convertimos un json a un objeto php
            $params = json_decode($json);

            $email =(isset($params->email))? $params->email: null;
            $password =(isset($params->password))? $params->password: null;
            $hash = (isset($params->hash))? $params->hash: null; 

            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "This email is not valid";
            $validateEmail = $this->get("validator")->validate($email,$emailConstraint);

            /*Cifrar contraseña*/
            $pwd = hash('sha256',$password);

            if($email !=null && count($validateEmail) == 0 && $password != null){

                 $jwt_auth = $this->get(JwtAuth::class);

                 if($hash == null || $hash == false){

                    $signup = $jwt_auth->signup($email,$pwd);
                 }else{

                    $signup = $jwt_auth->signup($email,$pwd,true);
                 }

                return $this->json($signup);

            }else{
                
                $data = array(
                    'status'=>'error',
                    'data'=>'Email or password incorrect'
                    );
            }
        
       }

       return $helpers->json($data);
    }

    #Se usa el parametro Request para poder recibir para
    #poder recibir metodos por POST y HTTP
    public function pruebasAction(Request $request)
    {
        #REcogemos una variable que llegara por Post con el nombr
        #authorization

        $jwt_auth = $this->get(JwtAuth::class);
        
        $helpers = $this->get(Helpers::class);

        $token = $request->get("authorization", null);

        #Con la condición comprobamos la llegada del token y
        #si este correcto
        if($token && $jwt_auth->checkToken($token)){

            $em = $this->getDoctrine()->getManager();
            
            $userRepo = $em->getRepository('BackendBundle:User');
            
            $users = $userRepo->findAll();
            
            return $helpers->json(array(
                'status' => 'success',
                'users'=>$users
                ));

        }else{

            return $helpers->json(array(
            'status' => 'Error',
            'data'=>'Login incorrecto'
            ));            

        }

        
    }


}
