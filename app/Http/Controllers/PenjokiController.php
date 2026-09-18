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
            ->get();

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
        $documents = Document::where(
            'penjoki_id',
            $request->user()->id
        )
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar dokumen yang saya ambil',
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

        $document->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Status dokumen berhasil diperbarui',
            'document' => $document,
        ]);
    }
}
