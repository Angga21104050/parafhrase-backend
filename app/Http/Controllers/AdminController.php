<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json([
            'message' => 'Admin endpoint berhasil diakses',
            'user' => $request->user(),
        ]);
    }

    public function documents()
    {
        $documents = Document::with([
            'user:id,name,email,role',
            'penjoki:id,name,email,role'
        ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar semua dokumen berhasil diambil',
            'documents' => $documents,
        ]);
    }
}
