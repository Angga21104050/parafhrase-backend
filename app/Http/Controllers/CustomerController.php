<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json([
            'message' => 'Customer endpoint berhasil diakses',
            'user' => $request->user(),
        ]);
    }

    public function index(Request $request)
    {
        $documents = Document::where(
            'user_id',
            $request->user()->id
        )
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar dokumen berhasil diambil',
            'documents' => $documents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240',
            ],
            'customer_note' => [
                'nullable',
                'string',
            ],
        ]);

        $file = $validated['document'];

        $path = $file->store('documents/original');

        $document = Document::create([
            'user_id' => $request->user()->id,
            'penjoki_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'original_path' => $path,
            'customer_note' => $validated['customer_note'] ?? null,
            'status' => 'PENDING',
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil diupload',
            'document' => $document,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $document = Document::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail dokumen berhasil diambil',
            'document' => $document,
        ]);
    }

    public function downloadResult(Request $request, $id)
    {
        $document = Document::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan',
            ], 404);
        }

        if ($document->status !== 'COMPLETED') {
            return response()->json([
                'message' => 'Hasil dokumen belum tersedia',
            ], 400);
        }

        if (!$document->result_path) {
            return response()->json([
                'message' => 'File hasil belum tersedia',
            ], 404);
        }

        if (!Storage::exists($document->result_path)) {
            return response()->json([
                'message' => 'File hasil tidak ditemukan di server',
            ], 404);
        }

        return Storage::download(
            $document->result_path,
            $document->result_filename
        );
    }
}
