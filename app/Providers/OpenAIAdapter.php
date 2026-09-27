<?php
declare(strict_types=1);
namespace App\Providers;
use App\Config;use App\HttpException;use App\ProviderAdapter;

final class OpenAIAdapter implements ProviderAdapter {
  public function generate(array $connector,array $inputs):array {
    $key=Config::get('OPENAI_API_KEY');if(!$key)throw new HttpException('provider_not_configured','OpenAI API key is not configured on the server.',503);
    $content=[['type'=>'input_text','text'=>$inputs['_prompt']??'']];
    foreach($inputs as $name=>$value){if(!is_array($value)||!isset($value['kind']))continue;
      $bytes=$this->bytes($value);
      if($value['kind']==='image')$content[]=['type'=>'input_image','image_url'=>'data:'.($value['mime_type']?:'image/jpeg').';base64,'.base64_encode($bytes),'detail'=>'auto'];
      elseif($value['kind']==='file')$content[]=['type'=>'input_file','filename'=>$value['name']??$name.'.bin','file_data'=>base64_encode($bytes)];
    }
    $schema=json_decode($connector['output_schema_json'],true);if(!is_array($schema))throw new HttpException('invalid_schema','Connector output schema is invalid.',422);
    $body=['model'=>$connector['model'],'input'=>[
      ['role'=>'developer','content'=>[['type'=>'input_text','text'=>$connector['system_instructions']]]],
      ['role'=>'user','content'=>$content]
    ],'text'=>['format'=>['type'=>'json_schema','name'=>'connector_output','description'=>$connector['description']?:'Structured connector output.','schema'=>$schema,'strict'=>true]]];
    $result=HttpClient::json('https://api.openai.com/v1/responses',$body,['Authorization: Bearer '.$key]);
    $text=is_string($result['output_text']??null)?$result['output_text']:'';
    if($text===''&&isset($result['output']))foreach($result['output'] as $item)foreach(($item['content']??[]) as $part)if(($part['type']??'')==='output_text')$text.=$part['text']??'';
    if($text==='')throw new HttpException('provider_empty_output','OpenAI returned no text output.',502);
    $u=$result['usage']??[];
    return ['raw_text'=>$text,'input_tokens'=>isset($u['input_tokens'])?(int)$u['input_tokens']:null,'output_tokens'=>isset($u['output_tokens'])?(int)$u['output_tokens']:null,'total_tokens'=>isset($u['total_tokens'])?(int)$u['total_tokens']:null];
  }
  public function listModels():array{
    $key=Config::get('OPENAI_API_KEY');if(!$key)throw new HttpException('provider_not_configured','OpenAI API key is not configured on the server.',503);
    $data=HttpClient::getJson('https://api.openai.com/v1/models',['Authorization: Bearer '.$key]);$out=[];
    foreach(($data['data']??[]) as $m)$out[]=['id'=>(string)($m['id']??''),'name'=>(string)($m['id']??''),'owner'=>(string)($m['owned_by']??'')];
    usort($out,fn($a,$b)=>strcmp($a['id'],$b['id']));return $out;
  }
  private function bytes(array $v):string{
    if(!empty($v['tmp_path'])){$b=@file_get_contents($v['tmp_path']);if($b===false)throw new HttpException('file_read_error','Uploaded file could not be read.',422);return $b;}
    $b=base64_decode((string)($v['base64']??''),true);if($b===false)throw new HttpException('file_read_error','Base64 file data is invalid.',422);return $b;
  }
}

final class HttpClient {
  public static function json(string $url,array $body,array $headers=[]):array{
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_SLASHES),CURLOPT_HTTPHEADER=>array_merge(['Content-Type: application/json'],$headers),CURLOPT_TIMEOUT=>Config::int('PROVIDER_TIMEOUT_SECONDS',60),CURLOPT_CONNECTTIMEOUT=>10]);
    $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);
    if($raw===false)throw new HttpException('provider_timeout',$err?:'Provider request failed.',504);
    $data=json_decode($raw,true);
    if($code<200||$code>=300){$msg=is_array($data)?($data['error']['message']??$data['message']??'Provider request failed.'):'Provider request failed.';throw new HttpException('provider_error',mb_substr((string)$msg,0,500),$code===429?429:502);}
    if(!is_array($data))throw new HttpException('provider_invalid_response','Provider returned invalid JSON.',502);return $data;
  }
  public static function getJson(string $url,array $headers=[]):array{
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>Config::int('PROVIDER_TIMEOUT_SECONDS',60),CURLOPT_CONNECTTIMEOUT=>10]);
    $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);
    if($raw===false)throw new HttpException('provider_timeout',$err?:'Provider request failed.',504);
    $data=json_decode($raw,true);
    if($code<200||$code>=300||!is_array($data)){$msg=is_array($data)?($data['error']['message']??'Provider request failed.'):'Provider returned invalid JSON.';throw new HttpException('provider_error',mb_substr((string)$msg,0,500),$code===429?429:502);}
    return $data;
  }
}
