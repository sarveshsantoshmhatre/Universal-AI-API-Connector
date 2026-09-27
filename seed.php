<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
use App\ConnectorService;use App\Config;
$db=app('db');$service=new ConnectorService($db);
if($service->listWithStats()){echo "Connectors already exist; seed skipped.\n";exit;}
$cardKey=Config::get('SEED_OPENAI_KEY')?:'uai_demo_card_'.bin2hex(random_bytes(18));
$articleKey=Config::get('SEED_GEMINI_KEY')?:'uai_demo_article_'.bin2hex(random_bytes(18));
$card=$service->create([
'name'=>'Card Scanner','description'=>'Extract structured contact information from a business-card image.','provider'=>'openai','model'=>Config::get('SEED_OPENAI_MODEL','gpt-5.6-luna'),
'system_instructions'=>'You are a business card extraction system. Extract the person name, company, designation, phone, email and website from the supplied image. Return only valid JSON matching the configured output structure.',
'input_schema'=>[['name'=>'image','type'=>'image','required'=>true,'description'=>'Business card image.']],
'output_schema'=>['type'=>'object','properties'=>['name'=>['type'=>'string'],'company'=>['type'=>'string'],'designation'=>['type'=>'string'],'phone'=>['type'=>'string'],'email'=>['type'=>'string'],'website'=>['type'=>'string']],'required'=>['name','company','designation','phone','email','website'],'additionalProperties'=>false],'is_active'=>true],$cardKey);
$article=$service->create([
'name'=>'Article Writer','description'=>'Generate structured article content from a topic and writing parameters.','provider'=>'gemini','model'=>Config::get('SEED_GEMINI_MODEL','gemini-2.5-flash'),
'system_instructions'=>'You are a professional article writer. Generate an article from the supplied topic and parameters. Return only valid JSON matching the configured output structure.',
'input_schema'=>[
['name'=>'topic','type'=>'text','required'=>true,'description'=>'Article topic','max_length'=>5000],
['name'=>'keywords','type'=>'text','required'=>false,'description'=>'Optional keywords'],
['name'=>'word_count','type'=>'number','required'=>false,'description'=>'Target word count','default'=>800,'min'=>100,'max'=>5000],
['name'=>'options','type'=>'json','required'=>false,'description'=>'Tone and other writing options','default'=>['tone'=>'professional']]],
'output_schema'=>['type'=>'object','properties'=>['title'=>['type'=>'string'],'summary'=>['type'=>'string'],'article'=>['type'=>'string'],'keywords'=>['type'=>'array','items'=>['type'=>'string']]],'required'=>['title','summary','article','keywords'],'additionalProperties'=>false],'is_active'=>true],$articleKey);
echo "Card Scanner API key: {$card['new_api_key']}\nArticle Writer API key: {$article['new_api_key']}\nDocs: /docs/{$card['slug']} and /docs/{$article['slug']}\n";
