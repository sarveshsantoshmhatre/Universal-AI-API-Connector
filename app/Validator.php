<?php
declare(strict_types=1);
namespace App;

final class Validator {
  public static function validateInputs(array $schema,array &$inputs):void {
    foreach($schema as $field){
      $name=(string)($field['name']??'');
      $type=strtolower((string)($field['type']??'text'));
      $required=(bool)($field['required']??false);
      if($name===''||!array_key_exists($name,$inputs)){
        if($required) throw new HttpException('validation_error',"Missing required input: {$name}.",422);
        if(array_key_exists('default',$field)) $inputs[$name]=$field['default']; else continue;
      }
      $value=$inputs[$name];
      if(in_array($type,['image','file'],true)){
        $valid=is_array($value)&&(isset($value['tmp_path'])||isset($value['base64']));
        if(!$valid) throw new HttpException('validation_error',"Input '{$name}' must be a valid uploaded file.",422);
        continue;
      }
      if($type==='number'){
        if(!is_numeric($value)) throw new HttpException('validation_error',"Input '{$name}' must be a number.",422);
        $inputs[$name]=$value+0;
        if(isset($field['min'])&&$inputs[$name]<$field['min']) throw new HttpException('validation_error',"Input '{$name}' is below the minimum.",422);
        if(isset($field['max'])&&$inputs[$name]>$field['max']) throw new HttpException('validation_error',"Input '{$name}' is above the maximum.",422);
      } elseif($type==='boolean'){
        if($value===true||$value===false) {}
        elseif($value==='true'||$value==='1'||$value===1) $inputs[$name]=true;
        elseif($value==='false'||$value==='0'||$value===0) $inputs[$name]=false;
        else throw new HttpException('validation_error',"Input '{$name}' must be boolean.",422);
      } elseif($type==='json'){
        if(is_string($value)){
          $decoded=json_decode($value,true);
          if(json_last_error()!==JSON_ERROR_NONE) throw new HttpException('validation_error',"Input '{$name}' must contain valid JSON.",422);
          $inputs[$name]=$decoded;
        }
      } else {
        $inputs[$name]=(string)$value;
        if(isset($field['max_length'])&&mb_strlen($inputs[$name])>(int)$field['max_length'])
          throw new HttpException('validation_error',"Input '{$name}' exceeds the maximum length.",422);
      }
      if(isset($field['enum'])&&is_array($field['enum'])&&!in_array($inputs[$name],$field['enum'],true))
        throw new HttpException('validation_error',"Input '{$name}' must be one of the configured values.",422);
    }
  }

  public static function normalizeOutput(string $raw,array $schema):array {
    $text=trim($raw);$fence=chr(96).chr(96).chr(96);
    if(str_starts_with($text,$fence)){ $text=preg_replace('/^'.preg_quote($fence,'/').'(?:json)?\s*/i','',$text)??$text; $text=preg_replace('/\s*'.preg_quote($fence,'/').'$/','',$text)??$text; $text=trim($text); }
    $data=json_decode($text,true);
    if(json_last_error()!==JSON_ERROR_NONE){$candidate=self::extract($text);if($candidate!==null)$data=json_decode($candidate,true);}
    if(json_last_error()!==JSON_ERROR_NONE||$data===null) throw new HttpException('invalid_provider_output','The AI provider returned malformed JSON.',502);
    $errors=[];self::check($data,$schema,'$',$errors);
    if($errors) throw new HttpException('output_schema_mismatch',implode(' ',array_slice($errors,0,3)),502);
    return $data;
  }

  private static function extract(string $text):?string {
    $positions=[];foreach(['{','['] as $char){$p=strpos($text,$char);if($p!==false)$positions[]=$p;}
    if(!$positions)return null;$start=min($positions);$open=$text[$start];$close=$open==='{'?'}':']';$depth=0;$quoted=false;$escaped=false;
    for($i=$start,$len=strlen($text);$i<$len;$i++){ $c=$text[$i];
      if($quoted){if($escaped){$escaped=false;continue;}if($c==='\\'){$escaped=true;continue;}if($c==='"')$quoted=false;continue;}
      if($c==='"'){$quoted=true;continue;}if($c===$open)$depth++;elseif($c===$close){$depth--;if($depth===0)return substr($text,$start,$i-$start+1);}
    } return null;
  }

  private static function check(mixed $value,array $schema,string $path,array &$errors):void {
    $type=strtolower((string)($schema['type']??''));
    if($type==='object'){
      if(!is_array($value)||array_is_list($value)){$errors[]="$path must be an object.";return;}
      foreach(($schema['required']??[]) as $required)if(!array_key_exists($required,$value))$errors[]="$path.$required is required.";
      foreach(($schema['properties']??[]) as $key=>$child)if(array_key_exists($key,$value))self::check($value[$key],(array)$child,"$path.$key",$errors);
    } elseif($type==='array'){
      if(!is_array($value)||!array_is_list($value)){$errors[]="$path must be an array.";return;}
      if(isset($schema['items']))foreach($value as $i=>$child)self::check($child,(array)$schema['items'],"$path[$i]",$errors);
    } elseif($type==='string'&&!is_string($value))$errors[]="$path must be a string.";
    elseif(in_array($type,['number','integer'],true)&&(!is_int($value)&&!is_float($value)))$errors[]="$path must be numeric.";
    elseif($type==='boolean'&&!is_bool($value))$errors[]="$path must be boolean.";
  }
}
