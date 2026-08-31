<?php

namespace App\Exports;

use App\Models\OrderPerbaikan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderPerbaikanExport
{
    protected $dateFrom;
    protected $dateTo;
    protected $selectedIds;
    protected $status;

    public function __construct($dateFrom = null, $dateTo = null, array $selectedIds = [], $status = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->selectedIds = array_filter($selectedIds, function($value) {
            return is_numeric($value) && $value > 0;
        });
        $this->status = $status;
    }

    public function download($fileName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'No. Order',
            'Tanggal',
            'Peminta',
            'Unit Proses',
            'Unit Penerima',
            'Jenis Barang',
            'Kode Inventaris',
            'Nama Barang',
            'Lokasi',
            'Prioritas',
            'Status',
            'Keluhan',
            'Tindak Lanjut',
            'Penanggung Jawab',
            'Tanggal Selesai'
        ];

        // Style the headers
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E2EFDA',
                ],
            ],
        ]);

        // Set headers
        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        // Get data
        $query = OrderPerbaikan::query();
        
        // Add necessary relationships
        $query->with(['creator', 'location', 'history']);

        // Apply filters
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        } else {
            if ($this->dateFrom) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            }
            if ($this->status) {
                $query->where('status', $this->status);
            }
        }

        // Get all orders
        $orders = $query->orderBy('created_at', 'desc')->get();

        // Log for debugging
        Log::info('Export Orders Count: ' . $orders->count(), [
            'selected_ids' => $this->selectedIds,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->status
        ]);

        // Add data rows
        $row = 2;
        foreach ($orders as $order) {
            // Map status to display text
            $status = match($order->status) {
                'open' => 'Open',
                'pending' => 'Pending',
                'in_progress' => 'Dalam Proses',
                'completed' => 'Selesai',
                'confirmed' => 'Dikonfirmasi',
                'rejected' => 'Ditolak',
                default => ucfirst($order->status)
            };

            // Get completion date based on status and updated_at
            $completionDate = null;
            if (in_array($order->status, ['confirmed', 'completed'])) {
                // Get the history entry when the order was completed/confirmed
                $completionHistory = $order->history()
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($completionHistory) {
                    $completionDate = Carbon::parse($completionHistory->created_at)->format('d/m/Y H:i');
                }
            }

            // Get latest follow up from history
            $latestHistory = $order->history()
                ->whereNotNull('follow_up')
                ->orderBy('created_at', 'desc')
                ->first();

            $data = [
                $order->nomor ?? '-',
                $order->tanggal ? Carbon::parse($order->tanggal)->format('d/m/Y H:i') : '-',
                $order->nama_peminta ?? $order->creator->name ?? '-',
                $order->unit_proses ?? '-',
                $order->unit_penerima ?? '-',
                $order->jenis_barang ?? '-',
                $order->kode_inventaris ?? '-',
                $order->nama_barang ?? '-',
                $order->location ? $order->location->name : '-',
                $order->prioritas ?? '-',
                $status,
                $order->keluhan ?? '-',
                $latestHistory ? $latestHistory->follow_up : ($order->follow_up ?? '-'),
                $order->nama_penanggung_jawab ?? '-',
                $completionDate ?? '-'
            ];

            foreach ($data as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue($column . $row, $value);
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set borders for all cells
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Create the writer
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        // Save to php output
        $writer->save('php://output');
        exit;
    }
} 