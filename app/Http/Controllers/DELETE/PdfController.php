<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Permission;
use PDF;



class PdfController extends Controller
{   
    public function index($document, Request $request)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.'.$document);
        return $pdf->download('document.pdf');
        PDF::SetTitle('Hello World');
        PDF::setFontSubsetting(true);
        $tagvs = [
            'div' => [
                0 => ['h' => 0, 'n' => 0],
                1 => ['h' => 0, 'n' => 0]
            ],
            'p' => [
                0 => ['h' => 0, 'n' => 0],
                1 => ['h' => 0, 'n' => 0]
            ],
            'table' => [
                0 => ['h' => 0, 'n' => 0],
                1 => ['h' => 0, 'n' => 0]
            ],
            'tr' => [
                0 => ['h' => 0, 'n' => 0],
                1 => ['h' => 0, 'n' => 0]
            ],
            'td' => [
                0 => ['h' => 0, 'n' => 0],
                1 => ['h' => 0, 'n' => 0]
            ]
        ];
        PDF::setHtmlVSpace($tagvs);
        // set font
        PDF::SetFont('freeserif', '', 12);
        PDF::AddPage();
        $html = view('pdf.invoice')->render();
        
        PDF::writeHTML($html, true, false, true, false, '');
        PDF::Output('hello_world.pdf');
    }

}

  