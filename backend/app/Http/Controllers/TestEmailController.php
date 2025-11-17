<?php

namespace App\Http\Controllers;

use App\Services\ResendMailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestEmailController extends Controller
{
    protected ResendMailService $resendService;

    public function __construct(ResendMailService $resendService)
    {
        $this->resendService = $resendService;
    }

    /**
     * Enviar email de prueba usando Resend SDK
     */
    public function testResendSDK(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->resendService->sendTestEmail($request->email);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Enviar email personalizado
     */
    public function sendCustomEmail(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'html' => 'required|string'
        ]);

        $result = $this->resendService->sendEmail([
            'to' => [$request->to],
            'subject' => $request->subject,
            'html' => $request->html,
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Ejemplo básico como el que proporcionaste
     */
    public function basicResendExample(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // Usar el SDK directamente como en tu ejemplo
            $resend = app(\Resend\Client::class);

            $result = $resend->emails->send([
                'from' => 'Sistema de Citas <onboarding@resend.dev>',
                'to' => [$request->email],
                'subject' => 'Hello World desde Laravel',
                'html' => '<h1>¡Funciona!</h1><p>Este correo fue enviado usando Resend SDK en Laravel.</p>'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo enviado exitosamente',
                'id' => $result->id ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}