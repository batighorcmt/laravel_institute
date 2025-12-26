<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Receipt;

class ReceiptController extends Controller
{
    public function show(int $id)
    {
        $receipt = Receipt::findOrFail($id);
        return response()->json(['receipt' => $receipt]);
    }
}
