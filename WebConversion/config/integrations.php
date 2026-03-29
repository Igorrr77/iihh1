<?php

return [
    'video' => [
        'providers' => ['youtube', 'vimeo', 'kinescope', 'bunny'],
        'default_quality' => '1080p',
    ],
    'payments' => [
        'providers' => ['stripe', 'wayforpay', 'braintree', 'paypal'],
    ],
    'email' => [
        'providers_top3' => ['sendgrid', 'mailgun', 'postmark'],
    ],
    'sms_top5' => ['twilio', 'messagebird', 'vonage', 'plivo', 'sinch'],
    'voice_top5' => ['twilio_voice', 'vonage_voice', 'plivo_voice', 'telnyx', 'sinch_voice'],
    'crm' => ['salesforce', 'hubspot', 'pipedrive', 'zoho', 'microsoft_dynamics', 'amocrm', 'bitrix24'],
    'messengers' => ['telegram', 'vk', 'whatsapp', 'viber', 'facebook_messenger'],
];
