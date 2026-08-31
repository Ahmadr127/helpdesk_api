<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with('user')->latest();
        
        // Filter by type if provided
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        
        // Filter by time period
        if ($request->has('period') && $request->period !== 'all') {
            if ($request->period === 'custom' && $request->has(['start_date', 'end_date'])) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            } else {
                $days = (int) $request->period;
                $query->where('created_at', '>=', now()->subDays($days));
            }
        }
        
        $reports = $query->paginate(10);
        
        // Get summary statistics
        $totalReports = Report::count();
        $bugReports = Report::where('type', 'bug')->count();
        $featureRequests = Report::where('type', 'feature')->count();

        return view('admin.reports.index', compact('reports', 'totalReports', 'bugReports', 'featureRequests'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'period' => 'required|in:7,30,90,custom'
        ]);

        $query = Report::query();
        
        // Filter berdasarkan periode
        switch ($request->period) {
            case '7':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case '30':
                $query->where('created_at', '>=', now()->subDays(30));
                break;
            case '90':
                $query->where('created_at', '>=', now()->subDays(90));
                break;
            case 'custom':
                // Untuk custom range, idealnya menambahkan input date range di form
                break;
        }

        $reports = $query->with('user')->latest()->get();

        // Bisa ditambahkan logika untuk export ke Excel/PDF
        return Excel::download(new ReportsExport($reports), 'reports.xlsx');
        
        // Atau kembali ke halaman dengan data yang sudah difilter
        // return back()->with('reports', $reports);
    }

    public function download(Report $report)
    {
        // Logika download report
        // Contoh: return response()->download($report->file_path);
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->route('admin.reports.index')
            ->with('success', 'Report deleted successfully');
    }

    public function updateStatus(Request $request, Report $report)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved'
        ]);

        $report->update(['status' => $request->status]);

        return redirect()->route('admin.reports.index')
            ->with('success', 'Report status updated successfully');
    }

    public function viewScreenshot(Report $report)
    {
        if (!$report->screenshot) {
            abort(404, 'No screenshot found in database');
        }

        $path = storage_path('app/public/' . $report->screenshot);
        
        \Log::info('Screenshot path: ' . $path);
        \Log::info('File exists: ' . (file_exists($path) ? 'Yes' : 'No'));
        
        if (!file_exists($path)) {
            abort(404, 'File not found in storage');
        }

        return response()->file($path);
    }
} 