<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class PenjokiController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json([
            'message' => 'Penjoki endpoint berhasil diakses',
            'user' => $request->user(),
        ]);
    }

    public function availableDocuments()
    {
        $documents = Document::where('status', 'PENDING')
            ->whereNull('penjoki_id')
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar dokumen yang tersedia untuk dijoki',
            'documents' => $documents,
        ]);
    }

    public function assign(Request $request, $id)
    {
        $document = Document::where('id', $id)
            ->where('status', 'PENDING')
            ->whereNull('penjoki_id')
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak tersedia atau sudah diambil oleh penjoki lain',
            ], 404);
        }

        $document->update([
            'penjoki_id' => $request->user()->id,
            'status' => 'IN_PROGRESS',
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil diambil',
            'document' => $document,
        ]);
    }

    public function download(Request $request, $id)
    {
        $document = Document::where('id', $id)
            ->where('penjoki_id', $request->user()->id)
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak tersedia atau bukan tugas Anda',
            ], 404);
        }

        if (!Storage::exists($document->original_path)) {
            return response()->json([
                'message' => 'File dokumen tidak ditemukan di server',
            ], 404);
        }

        return Storage::download(
            $document->original_path,
            $document->original_filename
        );
    }

    public function uploadResult(Request $request, $id)
    {
        $document = Document::where('id', $id)
            ->where('penjoki_id', $request->user()->id)
            ->where('status', 'IN_PROGRESS')
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak tersedia atau bukan tugas Anda',
            ], 404);
        }

        $validated = $request->validate([
            'result' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240',
            ],
        ]);

        $file = $validated['result'];

        $path = $file->store('documents/result');

        $document->update([
            'result_filename' => $file->getClientOriginalName(),
            'result_path' => $path,
            'status' => 'COMPLETED',
        ]);

        return response()->json([
            'message' => 'Hasil parafrase berhasil diupload',
            'document' => $document,
        ]);
    }

    public function myDocuments(Request $request)
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
            ],
        ]);

        $query = Document::where(
            'penjoki_id',
            $request->user()->id
        );

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $documents = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar dokumen saya berhasil diambil',
            'documents' => $documents,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:IN_PROGRESS,COMPLETED,CANCELLED',
            ],
        ]);

        $document = Document::where('id', $id)
            ->where('penjoki_id', $request->user()->id)
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak tersedia atau bukan tugas Anda',
            ], 404);
        }

        // Status hanya boleh diubah dari IN_PROGRESS
        if ($document->status !== 'IN_PROGRESS') {
            return response()->json([
                'message' => 'Status dokumen tidak dapat diubah lagi',
            ], 400);
        }

        // COMPLETED harus menggunakan uploadResult()
        if ($validated['status'] === 'COMPLETED') {
            return response()->json([
                'message' => 'Untuk menyelesaikan dokumen, silakan upload hasil terlebih dahulu',
            ], 400);
        }

        $document->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Status dokumen berhasil diperbarui',
            'document' => $document,
        ]);
    }
}
