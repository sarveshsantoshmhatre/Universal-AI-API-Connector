<?php
declare(strict_types=1);
namespace App;
final class ProviderManager {
  public function get(string $provider):ProviderAdapter {
    return match(strtolower($provider)){
      'openai'=>new Providers\OpenAIAdapter(),
      'gemini','google','google_gemini'=>new Providers\GeminiAdapter(),
      default=>throw new HttpException('unsupported_provider',"Unsupported AI provider: {$provider}.",422)
    };
  }
  public function generate(array $connector,array $inputs):array {
    $adapter=$this->get($connector['provider']);
    return $adapter->generate($connector,$inputs,PromptBuilder::build($connector,$inputs));
  }
  public function listModels(string $provider):array{return $this->get($provider)->listModels();}
}
