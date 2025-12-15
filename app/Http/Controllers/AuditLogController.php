<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        // Only owners can view audit logs
        $this->authorize('viewAuditLogs', auth()->user());

        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        // Filter by action if provided
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by user_id if provided
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->paginate(50);

        return response()->json([
            'data' => $logs,
            'message' => 'Audit logs retrieved successfully'
        ]);
    }

    /**
     * Get audit logs for a specific entity
     */
    public function getEntityLogs($entityType, $entityId)
    {
        // Only owners can view audit logs
        $this->authorize('viewAuditLogs', auth()->user());

        $logs = AuditLog::with('user')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $logs,
            'message' => 'Entity audit logs retrieved successfully'
        ]);
    }
}