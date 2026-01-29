<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDeclaracionJuradaRequest;
use App\Services\DjService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class DjController extends Controller
{
    public function generarPDF(Request $request)
    {
        $data = $request->all();

        $pdfService = new PdfService();
        $pdfContent = $pdfService->generarPdf($data);

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="previsualizacion_dj.pdf"');
    }

    public function saveDeclaracionJurada(SaveDeclaracionJuradaRequest $request)
    {

        $djService = new DjService();

        $saved = $djService->guardarDeclaracionJurada($request->validated());

        
    }
}
