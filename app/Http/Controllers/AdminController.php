<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $totalCustomers = User::where('role', 'user')->count();

        $totalPenjokis = User::where('role', 'penjoki')->count();

        $totalDocuments = Document::count();

        $pendingDocuments = Document::where('status', 'PENDING')->count();

        $inProgressDocuments = Document::where('status', 'IN_PROGRESS')->count();

        $completedDocuments = Document::where('status', 'COMPLETED')->count();

        $cancelledDocuments = Document::where('status', 'CANCELLED')->count();

        return response()->json([
            'message' => 'Dashboard admin berhasil diambil',

            'statistics' => [
                'total_customers' => $totalCustomers,
                'total_penjokis' => $totalPenjokis,
                'total_documents' => $totalDocuments,
                'pending_documents' => $pendingDocuments,
                'in_progress_documents' => $inProgressDocuments,
                'completed_documents' => $completedDocuments,
                'cancelled_documents' => $cancelledDocuments,
            ],

            'user' => $request->user(),
        ]);
    }

    public function documents(Request $request)
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
            ],
        ]);

        $query = Document::with([
            'user:id,name,email,role',
            'penjoki:id,name,email,role'
        ]);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $documents = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar dokumen berhasil diambil',
            'documents' => $documents,
        ]);
    }

    public function customers()
    {
        $customers = User::where('role', 'user')
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar semua customer berhasil diambil',
            'customers' => $customers,
        ]);
    }

    public function penjokis()
    {
        $penjokis = User::where('role', 'penjoki')
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar penjoki berhasil diambil',
            'penjokis' => $penjokis,
        ]);
    }

    public function showDocument($id)
    {
        $document = Document::with([
            'user:id,name,email,role',
            'penjoki:id,name,email,role'
        ])->find($id);

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

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
            ],
        ]);

        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan',
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
