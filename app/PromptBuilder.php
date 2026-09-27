<?php
declare(strict_types=1);
namespace App;
final class PromptBuilder {
  public static function build(array $connector,array $inputs):string {
    $safe=[];
    foreach($inputs as $name=>$value){
      $safe[$name]=is_array($value)&&isset($value['kind'])
        ? ['_type'=>$value['kind'],'filename'=>$value['name']??null,'mime_type'=>$value['mime_type']??null,'size'=>$value['size']??null]
        : $value;
    }
    return "Use the configured connector instructions exactly as the task specification.\n\nSubmitted connector inputs:\n"
      .json_encode($safe,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
      ."\n\nReturn only valid JSON matching the configured output schema.";
  }
}
