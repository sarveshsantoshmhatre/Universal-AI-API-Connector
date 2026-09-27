<?php
declare(strict_types=1);
namespace App;

final class RequestService {
  public function __construct(private Database $db,private ProviderManager $providers){}
  public static function collectInputs(array $connector):array{
    $schema=$connector['input_schema'];$json=!empty($_SERVER['CONTENT_TYPE'])&&str_contains(strtolower($_SERVER['CONTENT_TYPE']),'application/json');$body=$json?request_json():$_POST;$inputs=$body;
    foreach($schema as $field){
      $name=(string)$field['name'];$type=strtolower((string)$field['type']);
      if(in_array($type,['image','file'],true)&&isset($_FILES[$name])){
        $f=$_FILES[$name];if((int)$f['error']!==UPLOAD_ERR_OK)throw new HttpException('validation_error',"Upload failed for '{$name}'.",422);
        if((int)$f['size']>Config::int('MAX_UPLOAD_BYTES',10485760))throw new HttpException('request_too_large',"Upload '{$name}' exceeds the server upload limit.",413);
        $mime=self::safeMime($f['tmp_name'],$f['type']??'');if($type==='image'&&!str_starts_with($mime,'image/'))throw new HttpException('validation_error',"Input '{$name}' must be an image file.",422);
        $inputs[$name]=['kind'=>$type,'tmp_path'=>$f['tmp_name'],'name'=>$f['name'],'mime_type'=>$mime,'size'=>(int)$f['size']];
      } elseif(in_array($type,['image','file'],true)&&isset($body[$name])&&is_array($body[$name])&&isset($body[$name]['base64'])){
        $mime=(string)($body[$name]['mime_type']??'application/octet-stream');$b64=(string)$body[$name]['base64'];$size=(int)($body[$name]['size']??floor(strlen($b64)*0.75));
        if($size>Config::int('MAX_UPLOAD_BYTES',10485760))throw new HttpException('request_too_large',"Input '{$name}' exceeds the server upload limit.",413);
        if($type==='image'&&!str_starts_with($mime,'image/'))throw new HttpException('validation_error',"Input '{$name}' must be an image MIME type.",422);
        if(base64_decode($b64,true)===false)throw new HttpException('validation_error',"Input '{$name}' contains invalid base64.",422);
        $inputs[$name]=['kind'=>$type,'base64'=>$b64,'name'=>$body[$name]['filename']??"{$name}.bin",'mime_type'=>$mime,'size'=>$size];
      }
    }
    Validator::validateInputs($schema,$inputs);return $inputs;
  }
  public static function normalizeAdminTestInputs(array $connector,array $post,array $files,array $jsonBody):array{
    $inputs=$post?:$jsonBody;$_FILES=$files;
    foreach($connector['input_schema'] as $field){$name=$field['name'];$type=strtolower($field['type']);if(in_array($type,['image','file'],true)&&isset($files[$name])){
      $f=$files[$name];if((int)$f['error']!==UPLOAD_ERR_OK)throw new HttpException('validation_error',"Upload failed for '{$name}'.",422);if((int)$f['size']>Config::int('MAX_UPLOAD_BYTES',10485760))throw new HttpException('request_too_large',"Upload '{$name}' exceeds the server upload limit.",413);
      $mime=self::safeMime($f['tmp_name'],$f['type']??'');if($type==='image'&&!str_starts_with($mime,'image/'))throw new HttpException('validation_error',"Input '{$name}' must be an image file.",422);
      $inputs[$name]=['kind'=>$type,'tmp_path'=>$f['tmp_name'],'name'=>$f['name'],'mime_type'=>$mime,'size'=>(int)$f['size']];
    }}
    Validator::validateInputs($connector['input_schema'],$inputs);return $inputs;
  }
  public function execute(array $connector,array $inputs,bool $internal):array{
    $requestId='req_'.bin2hex(random_bytes(10));$started=microtime(true);
    try{
      $result=$this->providers->generate($connector,$inputs);$data=Validator::normalizeOutput($result['raw_text'],$connector['output_schema']);
      $elapsed=(int)round((microtime(true)-$started)*1000);$cost=$this->estimateCost($connector,$result);$this->safeLog($connector,$requestId,'success',$elapsed,$result,$cost,null,null);
      return ['success'=>true,'data'=>$data,'error'=>null,'meta'=>['request_id'=>$requestId,'response_time_ms'=>$elapsed,'provider'=>$connector['provider'],'model'=>$connector['model'],'usage'=>['input_tokens'=>$result['input_tokens'],'output_tokens'=>$result['output_tokens'],'total_tokens'=>$result['total_tokens']],'estimated_cost'=>$cost]];
    }catch(HttpException $e){
      $elapsed=(int)round((microtime(true)-$started)*1000);$this->safeLog($connector,$requestId,'failed',$elapsed,[],null,$e->type,$e->getMessage());
      return ['success'=>false,'data'=>null,'error'=>['type'=>$e->type,'message'=>$e->getMessage()],'meta'=>['request_id'=>$requestId,'response_time_ms'=>$elapsed,'provider'=>$connector['provider'],'model'=>$connector['model']]];
    }catch(\Throwable $e){
      error_log('[api-hub] execution '.$e::class.': '.$e->getMessage());$elapsed=(int)round((microtime(true)-$started)*1000);$this->safeLog($connector,$requestId,'failed',$elapsed,[],null,'provider_or_server_error','Unexpected provider failure');
      return ['success'=>false,'data'=>null,'error'=>['type'=>'provider_or_server_error','message'=>'The AI provider request failed.'],'meta'=>['request_id'=>$requestId,'response_time_ms'=>$elapsed,'provider'=>$connector['provider'],'model'=>$connector['model']]];
    }
  }
  private function estimateCost(array $connector,array $result):?float{
    if(($result['input_tokens']??null)===null&&($result['output_tokens']??null)===null)return null;$p=Config::pricing();$key=$connector['provider'].':'.$connector['model'];if(!isset($p[$key]))return null;
    return (($result['input_tokens']??0)/1000000)*(float)($p[$key]['input_per_million']??0)+(($result['output_tokens']??0)/1000000)*(float)($p[$key]['output_per_million']??0);
  }
  private function safeLog(array $c,string $requestId,string $status,int $elapsed,array $u,?float $cost,?string $et,?string $em):void{try{$this->db->run('INSERT INTO api_requests(connector_id,request_id,status,response_time_ms,input_tokens,output_tokens,total_tokens,estimated_cost,provider,model,error_type,error_message) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',[$c['id'],$requestId,$status,$elapsed,$u['input_tokens']??null,$u['output_tokens']??null,$u['total_tokens']??null,$cost,$c['provider'],$c['model'],$et,$em]);}catch(\Throwable $e){error_log('[api-hub] request log failure: '.$e->getMessage());}}
  private static function safeMime(string $tmp,string $fallback):string{$mime=function_exists('mime_content_type')?mime_content_type($tmp):'';return $mime?:($fallback?:'application/octet-stream');}
}
