<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
use App\Validator;use App\Slugger;
$schema=['type'=>'object','properties'=>['name'=>['type'=>'string'],'age'=>['type'=>'integer'],'active'=>['type'=>'boolean']],'required'=>['name'],'additionalProperties'=>false];
$data=Validator::normalizeOutput('{"name":"Ada","age":36,"active":true}',$schema);assert($data['name']==='Ada');assert(Slugger::make('Card Scanner!')==='card-scanner');
$bad=false;try{Validator::normalizeOutput('{"age":36}',$schema);}catch(Throwable $e){$bad=true;}assert($bad);echo "Smoke tests passed.\n";
