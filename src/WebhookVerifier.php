<?php declare(strict_types=1);
namespace Phessage\OneComm;
final class WebhookVerificationException extends \RuntimeException { public function __construct(public readonly string $reason,string $message){parent::__construct($message);} }
final class WebhookVerifier
{
    /** @param array<string,string|string[]> $headers @param null|callable(string,string):bool $claimReplay @return array<string,mixed> */
    public static function verify(string $rawBody,array $headers,string $secret,?callable $claimReplay=null,int $toleranceSeconds=300,?int $now=null):array
    {
        $normalized=[];foreach($headers as $name=>$value)$normalized[strtolower($name)]=is_array($value)?(string)($value[0]??''):(string)$value;
        $timestamp=$normalized['x-headless-webhook-timestamp']??'';$signature=$normalized['x-headless-webhook-signature']??'';$deliveryId=$normalized['x-headless-webhook-id']??'';
        if(!preg_match('/^\d{1,16}$/D',$timestamp)||$signature===''||$deliveryId==='')throw new WebhookVerificationException('invalid_headers','Required webhook headers are missing or malformed');
        if($toleranceSeconds<0||$toleranceSeconds>3600)throw new \InvalidArgumentException('toleranceSeconds must be between 0 and 3600');
        if(abs(($now??time())-(int)$timestamp)>$toleranceSeconds)throw new WebhookVerificationException('stale_timestamp','Webhook timestamp is outside the accepted tolerance');
        if(!str_starts_with($secret,'whsec_')||!hash_equals('sha256='.hash_hmac('sha256',$timestamp.'.'.$rawBody,$secret),$signature))throw new WebhookVerificationException('invalid_signature','Webhook signature is invalid');
        try{$envelope=json_decode($rawBody,true,32,JSON_THROW_ON_ERROR);}catch(\JsonException){throw new WebhookVerificationException('invalid_payload','Webhook body is not valid JSON');}
        if(!is_array($envelope)||($envelope['id']??null)!==$deliveryId||!is_string($envelope['event']??null)||!is_string($envelope['applicationId']??null)||!is_string($envelope['siteId']??null)||!is_string($envelope['occurredAt']??null))throw new WebhookVerificationException('invalid_payload','Webhook envelope does not match its delivery headers');
        if($claimReplay!==null&&!$claimReplay($deliveryId,$envelope['occurredAt']))throw new WebhookVerificationException('replayed_delivery','Webhook delivery was already processed');
        return $envelope;
    }
}
