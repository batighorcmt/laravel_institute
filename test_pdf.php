<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

try {
    $p = \App\Models\Payment::with(['student','school','paymentItems.studentFee.feeStructure.category'])->first();
    if(!$p) die("NO PAYMENT FOUND");
    echo "PAYMENT FOUND: " . $p->payment_number . "\n";
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.receipt_pdf', ['payment' => $p]);
    $pdf->output();
    echo "PDF GENERATED SUCCESSFULLY";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
