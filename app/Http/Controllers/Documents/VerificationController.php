<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentRecord;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function show(Request $request, string $code)
    {
        $record = DocumentRecord::where('code',$code)->firstOrFail();
        return view('principal.documents.verify', [
            'document' => $record,
            'student' => $record->student,
            'school' => $record->school,
        ]);
    }
}
