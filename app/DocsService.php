<?php
declare(strict_types=1);
namespace App;
final class DocsService {
  public function curl(array $c):string{
    $url=rtrim(Config::get('APP_URL',''),'/').'/api/'.$c['slug'];
    $cmd="curl -X POST ".escapeshellarg($url)." \\\n  -H 'X-API-Key: YOUR_CONNECTOR_API_KEY' \\\n";
    foreach($c['input_schema'] as $f){$n=$f['name'];$t=$f['type'];$cmd.=($t==='image'||$t==='file')?"  -F ".escapeshellarg($n.'=@./'.$n.'.bin')." \\\n":"  -F ".escapeshellarg($n.'=YOUR_'.strtoupper($n))." \\\n";}
    return rtrim($cmd," \\\n");
  }
  public function examplePayload(array $c):array{
    $out=[];foreach($c['input_schema'] as $f){$n=$f['name'];$t=$f['type'];$out[$n]=match($t){'number'=>1200,'boolean'=>true,'json'=>['tone'=>'professional'],'image'=>['filename'=>'sample.jpg','mime_type'=>'image/jpeg','base64'=>'<BASE64_IMAGE>'],'file'=>['filename'=>'sample.txt','mime_type'=>'text/plain','base64'=>'<BASE64_FILE>'],default=>'Example '.$n};}return $out;
  }
}
