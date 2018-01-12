<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\Task;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

class TaskController extends Controller{

	

	public function newAction(Request $request, $id = null){

		/*Debemos comprobar si el token que
		llega en la petición es el correcto*/

		$helpers = $this->get(Helpers::class);
		$jwtAuth = $this->get(JwtAuth::class);

		$token = $request->get("authorization",null);
		$checkToken = $jwtAuth->checkToken($token);

		if($checkToken){
			$identity = $jwtAuth->checkToken($token,true);
			$json = $request->get("json",null);

			if($json != null){
				$params = json_decode($json);

				$createdAt = new \Datetime("now");
				$updateAt = new \Datetime("now");

				$userId = ($identity->sub != null)?  $identity -> sub : null;
				$title = (isset($params->title))? $params->title:null;
				$description = (isset($params->description))? $params->description:null;
				$status = (isset($params->status))? $params->status:null;

				if($userId != null && $title != null){

					$em = $this->getDoctrine()->getManager();

					$user = $em->getRepository('BackendBundle:User')->findOneBy(
						array( 
								"id"=> $userId,
							));

					if($id == null){

						$task = new Task();
						$task->setUser($user);
						$task->setTitle($title);
						$task->setDescription($description);
						$task->setStatus($status);
						$task->setCreatedAt($createdAt);
						$task->setUpdatedAt($updateAt);

						$em->persist($task);
						$em->flush();

						$data = array(
						"status"=>"Succes",
						"code"=> 200,
						"data"=> $task,
						"msg"=>"Task has created");

				}else{

						$task = $em->getRepository('BackendBundle:Task')->findOneBy(array(
								"id"=>$id
								));

						if(isset($identity->sub)&& $identity->sub == $task->getUser()->getId()){
			
							$task->setTitle($title);
							$task->setDescription($description);
							$task->setStatus($status);
							$task->setUpdatedAt($updateAt);

							$em->persist($task);
							$em->flush();

							$data = array(
							"status"=>"Succes",
							"code"=> 200,
							"data"=> $task,
							"msg"=>"Task has edited");

						}else{

							$data = array(
							"status"=>"Error",
							"code"=> 200,
							"msg"=>"Task updated, you are not owner");
						}

				}


				}else{
					
					$data = array(
					"status"=>"Error",
					"code"=> 200,
					"msg"=>"Task not created, validation failed");
				}

				
			}else{

				$data = array(
				"status"=>"error",
				"code"=> 200,
				"msg"=>"Task not created, params failed"
				);
			}

		}else{
			$data = array(
				"status"=>"Failure",
				"code"=> 400,
				"msg"=>"Authorization not valid"
				);
		}

		return $helpers->json($data);

	}


	public function tasksAction(Request $request){

		$helpers = $this->get(Helpers::class);
		$jwtAuth = $this->get(JwtAuth::class);

		$token = $request->get("authorization",null);
		$checkToken = $jwtAuth->checkToken($token);

		if($checkToken){

			$identity = $jwtAuth->checkToken($token,true);

			$em = $this->getDoctrine()->getManager();

			$dql = "SELECT t FROM BackendBundle:Task t WHERE t.user = {$identity->sub} ORDER BY t.id DESC";

			$query = $em->createQuery($dql);

			$page = $request->query->getInt('page',1);
			
			$paginator = $this->get('knp_paginator');
			
			$itemsPerPage = 10;

			$pagination = $paginator->paginate($query,$page,$itemsPerPage);

			$numeroTotalRegistros = $pagination->getTotalItemCount();

			$data = array(
				'status'=>'Success',
				'code'=>200,
				'msg' => 'Authorization correct',
				'numeroRegistros'=>$numeroTotalRegistros,
				'itemsPorPage' => $itemsPerPage,
				'totalPaginas'=> ceil($numeroTotalRegistros/$itemsPerPage),
				'data'=>$pagination
				);

		}else{

			$data = array(
				'status'=>'error',
				'code'=>400,
				'msg' => 'Authorization not valid'
				);

		}

		return $helpers->json($data);
	}

	public function taskAction(Request $request, $id = null){

		$helpers = $this->get(Helpers::class);
		$jwtAuth = $this->get(JwtAuth::class);

		$token = $request->get("authorization",null);
		$checkToken = $jwtAuth->checkToken($token);

		if($checkToken){

			$identity = $jwtAuth->checkToken($token,true);

			$em = $this->getDoctrine()->getManager();

			$task = $em->getRepository('BackendBundle:Task')->findOneBy(array('id'=> $id));

			if($task && is_object($task) &&$identity->sub == $task->getUser()->getId()){

				$data = array(
				'status'=>'Success',
				'code'=>200,
				'data' => $task
				);

			}else{

				$data = array(
				'status'=>'Error',
				'code'=>404,
				'msg' => 'Task not found'
				);

			}

		}else{
				$data = array(
				'status'=>'error',
				'code'=>400,
				'msg' => 'Authorization not valid'
				);
		}

		return $helpers->json($data);
	}

	public function searchAction(Request $request, $search = null){

		$helpers = $this->get(Helpers::class);
		$jwtAuth = $this->get(JwtAuth::class);

		$token = $request->get("authorization",null);
		$checkToken = $jwtAuth->checkToken($token);

		if($checkToken){

			$identity = $jwtAuth->checkToken($token,true);
			$em = $this->getDoctrine()->getManager();
			
			/*Filtro*/
			$filter = $request->get('filter',null);

			if(empty($filter)){

				$filter = null;

			}else if($filter == 1){

				$filter = 'new';

			}else if($filter == 2){
				
				$filter = 'todo';
			}

			else{
				$filter = 'finished';
			}

			$order = $request->get('order',null);

			if(empty($order)|| $order ==2){

				$order = 'DESC';

			}else{

				$order= 'ASC';
			}

			if($search != null){

				$dql = "SELECT t FROM BackendBundle:Task t ". "WHERE t.user = $identity->sub AND "."(t.title LIKE :search OR t.description  LIKE :search) ";

			}else{
					$dql = "SELECT t FROM BackendBundle:Task t "."WHERE t.user = $identity->sub";
				}

			//Set filter
			if($filter != null){

					$dql.=" AND t.status = :filter";

				}

			//Set order
			$dql .= " ORDER BY t.id $order";

			$query = $em->createQuery($dql)->setParameter('filter',"$filter");

			if(!empty($search)){
				$query->setParameter('search',"%$search%");
			}

			$tasks = $query->getResult();

			$data = array(
				'status'=>'Succes',
				'code'=>200,
				'tareas' => $tasks
				);

		}else{

			$data = array(
				'status'=>'Error',
				'code'=>400,
				'msg' => 'Authorization not valid'
				);

		}

			return $helpers->json($data);
	}
		

	public function removeAction(Request $request,$id = null){

		$helpers = $this->get(Helpers::class);
		$jwtAuth = $this->get(JwtAuth::class);

		$token = $request->get("authorization",null);
		$checkToken = $jwtAuth->checkToken($token);

		if($checkToken){

			$identity = $jwtAuth->checkToken($token,true);

			$em = $this->getDoctrine()->getManager();

			$task = $em->getRepository('BackendBundle:Task')->findOneBy(array('id'=> $id));

			if($task && is_object($task) &&$identity->sub == $task->getUser()->getId()){

				$em->remove($task);
				$em->flush();

				$data = array(
				'status'=>'Success',
				'code'=>200,
				'data' => $task
				);

			}else{

				$data = array(
				'status'=>'Error',
				'code'=>404,
				'msg' => 'Task not found'
				);

			}

		}else{
				$data = array(
				'status'=>'error',
				'code'=>400,
				'msg' => 'Authorization not valid'
				);
		}

		return $helpers->json($data);
	}
		
	
}