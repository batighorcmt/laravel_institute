<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ReceiptController extends Controller
{
    /**
     * API Response
     */
    public function show($id)
    {
        $payment = Payment::with(['student', 'paymentItems.studentFee.feeStructure.category'])->findOrFail($id);
        return response()->json(['receipt' => $payment]);
    }

    /**
     * Web View Rendering
     */
    public function showWeb(Request $request, $id)
    {
        $payment = Payment::with([
            'student.currentEnrollment.class',
            'student.currentEnrollment.section',
            'paymentItems.studentFee.feeStructure.category',
            'school'
        ])->findOrFail($id);

        return view('billing.receipt', compact('payment'));
    }

    /**
     * PDF Download — uses mPDF with full Kalpurush Bengali font support
     */
    public function downloadPdf($id)
    {
        try {
            $payment = Payment::with([
                'student.currentEnrollment.class',
                'student.currentEnrollment.section',
                'paymentItems.studentFee.feeStructure.category',
                'school'
            ])->findOrFail($id);

            $html = view('billing.receipt_pdf', compact('payment'))->render();

            // mPDF font configuration
            $defaultConfig     = (new ConfigVariables())->getDefaults();
            $defaultFontConfig = (new FontVariables())->getDefaults();

            $fontDir  = storage_path('fonts');
            $tempDir  = storage_path('app/mpdf_temp');
            if (!is_dir($tempDir)) { mkdir($tempDir, 0755, true); }

            $mpdf = new Mpdf([
                'mode'             => 'utf-8',
                'format'           => 'A4',
                'default_font'     => 'kalpurush',
                'fontDir'          => array_merge($defaultConfig['fontDir'], [$fontDir]),
                'fontdata'         => array_merge($defaultFontConfig['fontdata'], [
                    'kalpurush' => [
                        'R'          => 'kalpurush_normal_6661c53feba164b2226ce34f5d636de1.ttf',
                        'B'          => 'kalpurush_bold_6661c53feba164b2226ce34f5d636de1.ttf',
                        'useOTL'     => 0xFF,
                        'useKashida' => 75,
                    ],
                ]),
                'autoScriptToLang'        => true,
                'autoLangToFont'          => true,
                'allow_charset_conversion'=> true,
                'margin_top'    => 13,
                'margin_right'  => 13,
                'margin_bottom' => 13,
                'margin_left'   => 13,
                'tempDir'       => $tempDir,
            ]);

            $mpdf->WriteHTML($html);

            $pdfContent = $mpdf->Output('', 'S'); // return as string

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="Receipt-' . $payment->payment_number . '.pdf"');

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'পিডিএফ তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage(),
                'details' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
