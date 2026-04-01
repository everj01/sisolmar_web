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
        $prompt = "Eres un experto en extraccion y analisis de examenes de capacitacion. Analiza el siguiente texto de un examen y devuelve UNICAMENTE un JSON valido, sin texto adicional ni bloques markdown.\n\nESTRUCTURA REQUERIDA:\nEl JSON debe tener bloques 'A' (Teoria) y 'B' (Razonamiento). Cada bloque es un array de preguntas con:\n- 'pregunta': Texto de la pregunta.\n- 'opciones': Array de strings LIMPIOS, SIN prefijo de letra. Ejemplo correcto: [\"Verdadero\", \"Falso\"]. Incorrecto: [\"A. Verdadero\", \"B. Falso\"].\n- 'respuesta_correcta': La LETRA de la opcion correcta (A, B, C o D). Busca asteriscos (*), negritas, o cualquier marca visual que indique la respuesta. Si es una pregunta de Verdadero/Falso, determina cual es correcto por el contexto. Si no puedes identificarla, pon null.\n\nEJEMPLO DE RESPUESTA:\n{\"A\": [{\"pregunta\": \"Ejemplo de pregunta?\", \"opciones\": [\"Op1\", \"Op2\", \"Op3\", \"Op4\"], \"respuesta_correcta\": \"B\"}], \"B\": []}\n\nTEXTO DEL EXAMEN:\n$texto";

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un extractor de datos de exámenes que solo responde con JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $content = $result['choices'][0]['message']['content'];

            // Limpiar posibles etiquetas de markdown
            $content = str_replace(['```json', '```'], '', $content);

            return json_decode(trim($content), true);
        } catch (\Exception $e) {
            Log::error('Error en OpenAIService: ' . $e->getMessage());
            return null;
        }
    }
}
