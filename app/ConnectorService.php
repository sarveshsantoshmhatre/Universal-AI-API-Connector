<?php
declare(strict_types=1);
namespace App;

final class ConnectorService {
  public function __construct(private Database $db){}
  public function listWithStats():array{
    $rows=$this->db->all("SELECT c.*,
      (SELECT COUNT(*) FROM api_requests r WHERE r.connector_id=c.id) AS total_requests,
      (SELECT COUNT(*) FROM api_requests r WHERE r.connector_id=c.id AND r.status='success') AS successful_requests,
      (SELECT COUNT(*) FROM api_requests r WHERE r.connector_id=c.id AND r.status='failed') AS failed_requests,
      (SELECT MIN(r.request_timestamp) FROM api_requests r WHERE r.connector_id=c.id) AS first_used,
      (SELECT MAX(r.request_timestamp) FROM api_requests r WHERE r.connector_id=c.id) AS last_used,
      (SELECT AVG(r.response_time_ms) FROM api_requests r WHERE r.connector_id=c.id AND r.status='success') AS avg_response_time_ms
      FROM connectors c ORDER BY c.created_at DESC");
    return array_map(fn($r)=>$this->decode($r),$rows);
  }
  public function find(int $id):?array{$r=$this->db->one('SELECT * FROM connectors WHERE id=?',[$id]);return $r?$this->decode($r):null;}
  public function findBySlug(string $slug):?array{$r=$this->db->one('SELECT * FROM connectors WHERE slug=?',[$slug]);return $r?$this->decode($r):null;}
  public function create(array $payload,?string $forcedKey=null):array{
    $data=$this->validate($payload);$slug=Slugger::unique($data['name'],fn($s)=>(bool)$this->findBySlug($s));
    $plain=$forcedKey?:'uai_'.bin2hex(random_bytes(24));
    $this->db->run('INSERT INTO connectors(name,slug,description,provider,model,system_instructions,input_schema_json,output_schema_json,api_key_hash,api_key_prefix,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
      [$data['name'],$slug,$data['description'],$data['provider'],$data['model'],$data['system_instructions'],json_encode($data['input_schema']),json_encode($data['output_schema']),hash('sha256',$plain),substr($plain,0,10),$data['is_active']?1:0]);
    $c=$this->find($this->db->lastInsertId());$c['new_api_key']=$plain;return $c;
  }
  public function update(int $id,array $payload):array{
    $current=$this->find($id);if(!$current)throw new HttpException('not_found','Connector not found.',404);
    $data=$this->validate(array_merge($current,$payload));$slug=$current['slug'];
    if($data['name']!==$current['name'])$slug=Slugger::unique($data['name'],fn($s)=>(bool)$this->findBySlug($s)&&$s!==$current['slug']);
    $this->db->run('UPDATE connectors SET name=?,slug=?,description=?,provider=?,model=?,system_instructions=?,input_schema_json=?,output_schema_json=?,is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',
      [$data['name'],$slug,$data['description'],$data['provider'],$data['model'],$data['system_instructions'],json_encode($data['input_schema']),json_encode($data['output_schema']),$data['is_active']?1:0,$id]);
    return $this->find($id);
  }
  public function delete(int $id):void{if(!$this->db->run('DELETE FROM connectors WHERE id=?',[$id]))throw new HttpException('not_found','Connector not found.',404);}
  public function logs(int $id):array{return $this->db->all('SELECT request_id,status,response_time_ms,input_tokens,output_tokens,total_tokens,estimated_cost,provider,model,error_type,error_message,request_timestamp FROM api_requests WHERE connector_id=? ORDER BY id DESC LIMIT 100',[$id]);}
  private function validate(array $p):array{
    foreach(['name','provider','model','system_instructions'] as $k)if(trim((string)($p[$k]??''))==='')throw new HttpException('validation_error',ucfirst($k).' is required.',422);
    $inputs=$p['input_schema']??(is_string($p['input_schema_json']??null)?json_decode($p['input_schema_json'],true):null);
    $outputs=$p['output_schema']??(is_string($p['output_schema_json']??null)?json_decode($p['output_schema_json'],true):null);
    if(!is_array($inputs))throw new HttpException('validation_error','Input schema must be a JSON array.',422);
    if(!is_array($outputs)||($outputs['type']??null)!=='object')throw new HttpException('validation_error','Output schema root must be an object.',422);
    $seen=[];
    foreach($inputs as $f){$n=(string)($f['name']??'');$t=strtolower((string)($f['type']??''));if($n===''||!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/',$n))throw new HttpException('validation_error','Each input parameter needs a valid name.',422);if(!in_array($t,['text','number','boolean','image','file','json'],true))throw new HttpException('validation_error',"Unsupported input type for {$n}.",422);if(isset($seen[$n]))throw new HttpException('validation_error',"Duplicate input parameter: {$n}.",422);$seen[$n]=true;}
    return ['name'=>trim($p['name']),'description'=>trim((string)($p['description']??'')),'provider'=>strtolower(trim($p['provider'])),'model'=>trim($p['model']),'system_instructions'=>trim($p['system_instructions']),'input_schema'=>$inputs,'output_schema'=>$outputs,'is_active'=>filter_var($p['is_active']??true,FILTER_VALIDATE_BOOLEAN)];
  }
  private function decode(array $r):array{$r['id']=(int)$r['id'];$r['is_active']=(int)$r['is_active'];$r['input_schema']=json_decode($r['input_schema_json'],true)?:[];$r['output_schema']=json_decode($r['output_schema_json'],true)?:[];unset($r['api_key_hash']);return $r;}
}
