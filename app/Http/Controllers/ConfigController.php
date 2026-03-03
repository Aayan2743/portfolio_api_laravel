<?php

namespace App\Http\Controllers;

use App\Helpers\EnvHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

class ConfigController extends Controller
{
    public function updateEmailConfig(Request $request)
{
    $validator = Validator::make($request->all(), [
        'mail_mailer'       => 'required|string',
        'mail_host'       => 'required|string',
        'mail_port'       => 'required|numeric',
        'mail_username'   => 'required|string',
        'mail_password'   => 'required|string',
        'mail_encryption' => 'required|string',
        'mail_from_name'  => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()->first(),
        ], 422);
    }

    // Update ENV
    EnvHelper::setEnvValue('MAIL_MAILER', $request->mail_mailer);
    EnvHelper::setEnvValue('MAIL_HOST', $request->mail_host);
    EnvHelper::setEnvValue('MAIL_PORT', $request->mail_port);
    EnvHelper::setEnvValue('MAIL_USERNAME', $request->mail_username);
    EnvHelper::setEnvValue('MAIL_PASSWORD', $request->mail_password);
    EnvHelper::setEnvValue('MAIL_ENCRYPTION', $request->mail_encryption);
    EnvHelper::setEnvValue('MAIL_FROM_ADDRESS', $request->mail_username);
    EnvHelper::setEnvValue('MAIL_FROM_NAME', $request->mail_from_name);

    Artisan::call('config:clear');
    Artisan::call('cache:clear');

    return response()->json([
        'success' => true,
        'message' => 'Email configuration updated successfully'
    ]);
    }

    public function updateWhatsappConfig(Request $request)
{
    $validator = Validator::make($request->all(), [
        'whatsapp_enabled' => 'required|boolean',
        'whatsapp_url'     => 'required|string',
        'whatsapp_key'     => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()->first(),
        ], 422);
    }

    EnvHelper::setEnvValue('WHATSAPP_ENABLED', $request->whatsapp_enabled);
    EnvHelper::setEnvValue('WHATSAPP_BASE_URL', $request->whatsapp_url);
    EnvHelper::setEnvValue('WHATSAPP_API_KEY', $request->whatsapp_key);

    Artisan::call('config:clear');
    Artisan::call('cache:clear');

    return response()->json([
        'success' => true,
        'message' => 'WhatsApp configuration updated successfully'
    ]);
    }

    public function getEmailConfig()
{
    return response()->json([
        'success' => true,
        'data' => [
            'mail_mailer'     => config('mail.default'),
            'mail_host'       => config('mail.mailers.smtp.host'),
            'mail_port'       => config('mail.mailers.smtp.port'),
            'mail_username'   => config('mail.mailers.smtp.username'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),
            'mail_from_name'  => config('mail.from.name'),
            'mail_from_address' => config('mail.from.address'),
        ]
    ]);
    }

    public function getWhatsappConfig()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'whatsapp_enabled' => config('services.whatsapp.enabled'),
                'whatsapp_url'     => config('services.whatsapp.base_url'),
                'whatsapp_key'     => config('services.whatsapp.api_key'),
            ]
        ]);
    }
}
