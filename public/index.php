<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
use App\Auth;use App\ConnectorService;use App\DocsService;use App\HttpException;use App\JsonResponse;use App\ProviderManager;use App\RequestService;

$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
try{
  $db=app('db');$connectors=new ConnectorService($db);$providers=new ProviderManager();$requests=new RequestService($db,$providers);$docs=new DocsService();
  if($path==='/health')JsonResponse::send(['success'=>true,'status'=>'ok','time'=>gmdate(DATE_ATOM)]);
  if($path==='/'&&$method==='GET'){require __DIR__.'/../app/views/dashboard.php';exit;}
  if(preg_match('#^/docs/([a-z0-9-]+)$#',$path,$m)&&$method==='GET'){$connector=$connectors->findBySlug($m[1]);if(!$connector)throw new HttpException('not_found','Connector not found.',404);require __DIR__.'/../app/views/docs.php';exit;}
  if(str_starts_with($path,'/api/connectors'))Auth::requireAdmin();
  if($path==='/api/connectors'&&$method==='GET')JsonResponse::send(['success'=>true,'data'=>$connectors->listWithStats(),'error'=>null]);
  if($path==='/api/connectors'&&$method==='POST')JsonResponse::send(['success'=>true,'data'=>$connectors->create(request_json()),'error'=>null],201);
  if(preg_match('#^/api/connectors/(\d+)$#',$path,$m)){
    $id=(int)$m[1];if($method==='GET'){if(!$c=$connectors->find($id))throw new HttpException('not_found','Connector not found.',404);JsonResponse::send(['success'=>true,'data'=>$c,'error'=>null]);}
    if($method==='PUT')JsonResponse::send(['success'=>true,'data'=>$connectors->update($id,request_json()),'error'=>null]);
    if($method==='DELETE'){$connectors->delete($id);JsonResponse::send(['success'=>true,'data'=>null,'error'=>null]);}
  }
  if(preg_match('#^/api/connectors/(\d+)/test$#',$path,$m)&&$method==='POST'){
    $c=$connectors->find((int)$m[1]);if(!$c)throw new HttpException('not_found','Connector not found.',404);
    $inputs=RequestService::normalizeAdminTestInputs($c,$_POST,$_FILES,request_json(false));JsonResponse::send($requests->execute($c,$inputs,true));
  }
  if(preg_match('#^/api/connectors/(\d+)/logs$#',$path,$m)&&$method==='GET')JsonResponse::send(['success'=>true,'data'=>$connectors->logs((int)$m[1]),'error'=>null]);
  if(preg_match('#^/api/connectors/(\d+)/docs$#',$path,$m)&&$method==='GET'){$c=$connectors->find((int)$m[1]);if(!$c)throw new HttpException('not_found','Connector not found.',404);JsonResponse::send(['success'=>true,'data'=>['url'=>'/docs/'.$c['slug']],'error'=>null]);}
  if(preg_match('#^/api/providers/([^/]+)/models$#',$path,$m)&&$method==='GET')JsonResponse::send(['success'=>true,'data'=>$providers->listModels($m[1]),'error'=>null]);
  if(preg_match('#^/api/([a-z0-9-]+)$#',$path,$m)&&$method==='POST'){
    $c=$connectors->findBySlug($m[1]);if(!$c)throw new HttpException('not_found','Endpoint not found.',404);
    Auth::requireConnectorKey($c['api_key_hash']);if((int)$c['is_active']!==1)throw new HttpException('inactive_connector','This connector is disabled.',403);
    $inputs=RequestService::collectInputs($c);$result=$requests->execute($c,$inputs,false);JsonResponse::send($result,$result['success']?200:502);
  }
  throw new HttpException('not_found','Route not found.',404);
}catch(HttpException $e){JsonResponse::send(['success'=>false,'data'=>null,'error'=>['type'=>$e->type,'message'=>$e->getMessage()]],$e->status);}
catch(Throwable $e){error_log('[api-hub] '.$e::class.': '.$e->getMessage());JsonResponse::send(['success'=>false,'data'=>null,'error'=>['type'=>'server_error','message'=>'An unexpected server error occurred.']],500);}
