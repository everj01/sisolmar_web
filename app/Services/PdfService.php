<?php

namespace App\Services;

class PdfService
{
    public function generarPdf(array $data)
    {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);

        $pdf->Cell(0, 10, utf8_decode('DECLARACIÓN JURADA - VISTA PREVIA'), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->Cell(0, 10, utf8_decode("Nombres: " . ($data['nombres_apellidos'] ?? '')), 0, 1);
        $pdf->Cell(0, 10, utf8_decode("DNI: " . ($data['dni'] ?? '')), 0, 1);
        $pdf->Cell(0, 10, utf8_decode("Correo: " . ($data['correo'] ?? '')), 0, 1);
        $pdf->Ln(10);

        $pdf->MultiCell(0, 8, utf8_decode("Declaro bajo juramento que los datos proporcionados son verídicos y completos."), 0, 'L');

        return $pdf->Output('S'); // “S” devuelve el contenido como string (no lo guarda)
    }
}