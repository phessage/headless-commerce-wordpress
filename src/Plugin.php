<?php declare(strict_types=1);
namespace Phessage\OneComm;
final class Plugin {
 public static function boot():void{add_action('admin_init',[self::class,'settings']);add_action('init',[self::class,'blocks']);add_shortcode('onecomm_products',[self::class,'shortcode']);}
 public static function settings():void{register_setting('onecomm','onecomm_api_url',['type'=>'string','sanitize_callback'=>'esc_url_raw']);register_setting('onecomm','onecomm_publishable_key',['type'=>'string','sanitize_callback'=>fn($value)=>str_starts_with((string)$value,'pk_')?sanitize_text_field($value):'']);}
 public static function blocks():void{register_block_type(__DIR__.'/../block',['render_callback'=>fn($attributes)=>self::render((int)($attributes['limit']??12))]);}
 public static function shortcode(array|string $attributes=[]):string{$values=shortcode_atts(['limit'=>12],is_array($attributes)?$attributes:[]);return self::render((int)$values['limit']);}
 public static function render(int $limit):string{$client=new CatalogClient((string)get_option('onecomm_api_url',''),(string)get_option('onecomm_publishable_key',''));$items=$client->products($limit);if(!$items)return '<p class="onecomm-empty">'.esc_html__('No products available.','onecomm').'</p>';$html='<div class="onecomm-grid">';foreach($items as $item){$name=esc_html((string)($item['name']??''));$description=esc_html((string)($item['description']??''));$amount=esc_html((string)($item['price']['amount']??''));$currency=esc_html((string)($item['price']['currency']??''));$html.='<article class="onecomm-product"><h3>'.$name.'</h3><p>'.$description.'</p><strong>'.$amount.' '.$currency.'</strong></article>'; }return $html.'</div>';}
}
