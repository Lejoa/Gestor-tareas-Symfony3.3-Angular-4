<?php
namespace AppBundle\Services; 

class Helpers
{
	public $manager;

	public function __construct($manager)
	{
		$this->manager =$manager;
	}

	public function holaMundo()
	{
		return "Hola Mundo desde mi servicio Symfony";
	}

	/*
		Convierte un array de objetos a JSON
	*/
	public function json($data)
	{
		#El uso de las barras en las siguientes lineas son "namespaces".
		#Necesitamos normalizar los JSON
		$normalizers = array(new \Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer());
		
		#Metodo que nos permite codificar JSON
		$encoders = array("json" => new \Symfony\Component\Serializer\Encoder\JsonEncoder());
		
		#Para serializar la información a JSON
		$serializer = new \Symfony\Component\Serializer\Serializer($normalizers,$encoders);
		
		$json = $serializer->serialize($data,'json');
		
		$response = new \Symfony\Component\HttpFoundation\Response();

		$response->setContent($json);

		#La información ya se encuentra enviando en formato JSON
		$response->headers->set('Content-Type','application/json');

		return $response;
	}


}