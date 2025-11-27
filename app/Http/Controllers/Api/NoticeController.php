<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => [],
            'meta' => ['message' => 'notices list placeholder']
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required','string'],
            'body' => ['required','string'],
            'publish_at' => ['nullable','date'],
        ]);

        return response()->json([
            'saved' => true,
            'notice' => $validated,
            'meta' => ['message' => 'notice create placeholder']
        ], 201);
    }
}
