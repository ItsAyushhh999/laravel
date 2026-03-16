<?php

namespace App\Services;

use Aws\Sns\SnsClient;

class SnsService
{
    protected $client;

    public function __construct()
    {
        $this->client = new SnsClient([
            'region' => config('services.sns.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.sns.key'),
                'secret' => config('services.sns.secret'),
            ],
            'endpoint' => config('services.sns.endpoint'),
        ]);
    }

    public function publish(string $message, string $subject = 'Notification')
    {
        return $this->client->publish([
            'TopicArn' => config('services.sns.topic_arn'),
            'Message' => $message,
            'Subject' => $subject,
        ]);
    }
}
