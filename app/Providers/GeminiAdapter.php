<?php
declare(strict_types=1);
namespace App\Providers;
use App\Config;use App\HttpException;use App\ProviderAdapter;use App\SchemaTools;

final class GeminiAdapter implements ProviderAdapter {
  private const BASE='https://generativelanguage.googleapis.com/v1beta';
  public function generate(array $connector,array $inputs):array{
    $key=Config::get('GEMINI_API_KEY');if(!$key)throw new HttpException('provider_not_configured','Gemini API key is not configured on the server.',503);
    $parts=[['text'=>$connector['system_instructions']],['text'=>$inputs['_prompt']??'']];
    foreach($inputs as $name=>$value){if(!is_array($value)||!isset($value['kind']))continue;$bytes=$this->bytes($value);$parts[]=['inline_data'=>['mime_type'=>$value['mime_type']?:'application/octet-stream','data'=>base64_encode($bytes)]];}
    $schema=json_decode($connector['output_schema_json'],true);if(!is_array($schema))throw new HttpException('invalid_schema','Connector output schema is invalid.',422);
    $body=['contents'=>[['role'=>'user','parts'=>$parts]],'generationConfig'=>['responseMimeType'=>'application/json','responseSchema'=>SchemaTools::toGemini($schema)]];
    $result=OpenAIAdapter_Http::json(self::BASE.'/models/'.rawurlencode($connector['model']).':generateContent',$body,['x-goog-api-key: '.$key]);
    $text='';foreach(($result['candidates'][0]['content']['parts']??[]) as $part)if(isset($part['text']))$text.=$part['text'];if($text==='')throw new HttpException('provider_empty_output','Gemini returned no text output.',502);
    $u=$result['usageMetadata']??[];return ['raw_text'=>$text,'input_tokens'=>isset($u['promptTokenCount'])?(int)$u['promptTokenCount']:null,'output_tokens'=>isset($u['candidatesTokenCount'])?(int)$u['candidatesTokenCount']:null,'total_tokens'=>isset($u['totalTokenCount'])?(int)$u['totalTokenCount']:null];
  }
  public function listModels():array{
    $key=Config::get('GEMINI_API_KEY');if(!$key)throw new HttpException('provider_not_configured','Gemini API key is not configured on the server.',503);
    $data=OpenAIAdapter_Http::get(self::BASE.'/models?pageSize=1000',['x-goog-api-key: '.$key]);$out=[];
    foreach(($data['models']??[]) as $m){$methods=$m['supportedGenerationMethods']??$m['supportedActions']??[];if($methods&&!in_array('generateContent',$methods,true))continue;$out[]=['id'=>preg_replace('#^models/#','',(string)($m['name']??'')),'name'=>(string)($m['displayName']??$m['name']??''),'description'=>(string)($m['description']??''),'input_token_limit'=>$m['inputTokenLimit']??null,'output_token_limit'=>$m['outputTokenLimit']??null];}
    usort($out,fn($a,$b)=>strcmp($a['id'],$b['id']));return $out;
  }
  private function bytes(array $v):string{if(!empty($v['tmp_path'])){$b=@file_get_contents($v['tmp_path']);if($b===false)throw new HttpException('file_read_error','Uploaded file could not be read.',422);return $b;}$b=base64_decode((string)($v['base64']??''),true);if($b===false)throw new HttpException('file_read_error','Base64 file data is invalid.',422);return $b;}
}

final class OpenAIAdapter_Http {
  public static function json(string $url,array $body,array $headers=[]):array{
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_SLASHES),CURLOPT_HTTPHEADER=>array_merge(['Content-Type: application/json'],$headers),CURLOPT_TIMEOUT=>Config::int('PROVIDER_TIMEOUT_SECONDS',60),CURLOPT_CONNECTTIMEOUT=>10]);
    $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);if($raw===false)throw new HttpException('provider_timeout',$err?:'Provider request failed.',504);$data=json_decode($raw,true);
    if($code<200||$code>=300){$msg=is_array($data)?($data['error']['message']??'Provider request failed.'):'Provider request failed.';throw new HttpException('provider_error',mb_substr((string)$msg,0,500),$code===429?429:502);}
    if(!is_array($data))throw new HttpException('provider_invalid_response','Provider returned invalid JSON.',502);return $data;
  }
  public static function get(string $url,array $headers=[]):array{$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>Config::int('PROVIDER_TIMEOUT_SECONDS',60),CURLOPT_CONNECTTIMEOUT=>10]);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);if($raw===false)throw new HttpException('provider_timeout',$err?:'Provider request failed.',504);$data=json_decode($raw,true);if($code<200||$code>=300||!is_array($data))throw new HttpException('provider_error','Gemini model request failed.',502);return $data;}
}
