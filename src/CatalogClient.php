<?php declare(strict_types=1);
namespace Phessage\OneComm;
final class CatalogClient {
 public function __construct(private string $baseUrl,private string $key){}
 public function products(int $limit=12):array{
  if(!str_starts_with($this->key,'pk_')||$this->baseUrl==='')return [];
  $cache='onecomm_products_'.hash('sha256',$this->baseUrl.$this->key.$limit);$cached=get_transient($cache);if(is_array($cached))return $cached;
  $response=wp_remote_get(rtrim($this->baseUrl,'/').'/v1/headless/products?limit='.max(1,min(100,$limit)),['timeout'=>8,'redirection'=>0,'headers'=>['Accept'=>'application/json','x-publishable-key'=>$this->key]]);
  if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200)return [];
  $decoded=json_decode(wp_remote_retrieve_body($response),true);$products=is_array($decoded['data']??null)?$decoded['data']:[];set_transient($cache,$products,MINUTE_IN_SECONDS);return $products;
 }
}
