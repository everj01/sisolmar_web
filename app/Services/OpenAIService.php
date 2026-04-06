<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->client = new Client([
            'base_uri'        => 'https://api.openai.com/v1/',
            'verify'          => false, // SSL workaround para entorno Windows
            'timeout'         => 280,   // Tiempo máximo de respuesta de la API (segundos)
            'connect_timeout' => 15,    // Tiempo máximo para establecer la conexión
            'headers'         => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Envía una solicitud a GPT-4o-mini para procesar el texto del examen.
     */
    public function procesarTextoExamen($texto)
    {
        $startTime = microtime(true);
        
        // IA Extractor Mode (Dictatorial - Letter Result)
        $systemMsg = "Eres un EXTRACTOR CIEGO Y OBEDIENTE. Tu única misión es transcribir lo que ves.\n"
                   . "REGLA DE ORO (IGNORA TODO TU CONOCIMIENTO):\n"
                   . "1. Si el texto tiene [MARCA_VERDE_CORRECTA], esa ES la respuesta correcta. NO LA CORRIJAS.\n"
                   . "2. Si tiene [MARCA_NEGRITA], es el enunciado.\n"
                   . "ESTRUCTURA COMPACTA:\n"
                   . "- p: enunciado.\n"
                   . "- o: opciones (No incluyas letras A), B) etc en el texto de la opción).\n"
                   . "- r: Usa exclusivamente las letras A, B, C, D, E para indicar la respuesta (basándose en el prefijo VERDE).\n"
                   . "JSON EXACTO: {\"A\":[{\"p\":\"\",\"o\":[],\"r\":\"A\"}], \"B\":[]}.";
        
        $userMsg = "Extrae TODO. Si ves [MARCA_VERDE_CORRECTA], pon la letra correspondiente (A para la primera, B para la segunda, etc.) en 'r' sin cuestionar nada.\n\nTEXTO:\n$texto";

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemMsg],
                        ['role' => 'user', 'content' => $userMsg],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2,
                    'max_tokens' => 10000,
                ],
            ]);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $rawBody = $response->getBody()->getContents();
            $result = json_decode($rawBody, true);
            $content = $result['choices'][0]['message']['content'];
            $usage = $result['usage'] ?? [];

            // Cálculo de costos para gpt-4o-mini (Precios actualizados)
            $promptTokens = $usage['prompt_tokens'] ?? 0;
            $completionTokens = $usage['completion_tokens'] ?? 0;
            
            // Tarifas: $0.150 / 1M Input, $0.600 / 1M Output
            $costInput = ($promptTokens / 1000000) * 0.15;
            $costOutput = ($completionTokens / 1000000) * 0.60;
            $totalCost = round($costInput + $costOutput, 6);

            return [
                'success' => true,
                'data'    => json_decode(trim($content), true),
                'metrics' => [
                    'tokens_input'  => $promptTokens,
                    'tokens_output' => $completionTokens,
                    'tokens_total'  => $usage['total_tokens'] ?? 0,
                    'costo_usd'     => $totalCost,
                    'tiempo_seg'    => $executionTime
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error en OpenAIService: ' . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }
}
