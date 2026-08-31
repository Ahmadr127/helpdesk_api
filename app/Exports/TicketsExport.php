<?php

namespace App\Exports;

use App\Models\Ticket;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class TicketsExport
{
    protected $dateFrom;
    protected $dateTo;
    protected $selectedIds;

    public function __construct($dateFrom = null, $dateTo = null, array $selectedIds = [])
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->selectedIds = array_filter($selectedIds, function($value) {
            return is_numeric($value) && $value > 0;
        });
    }

    public function download($fileName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'No Tiket',
            'Pengguna',
            'Kategori',
            'Departemen',
            'Prioritas',
            'Status',
            'Tanggal Dibuat',
            'Waktu Dibuat',
            'Waktu Mulai Proses',
            'Waktu Selesai',
            'Waktu Konfirmasi',
            'Deskripsi',
            'Catatan Admin',
            'Catatan Konfirmasi',
            'Total Durasi (Menit)',
            'Skor Kinerja'
        ];

        // Style the headers
        $sheet->getStyle('A1:P1')->applyFromArray([
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
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($columnLetter . '1', $header);
        }

        // Get data
        $query = Ticket::with(['user'])
            ->where('status', 'confirmed')
            ->where('user_confirmation', true);

        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        } else {
            if ($this->dateFrom) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            }
        }

        $tickets = $query->orderBy('user_confirmed_at', 'desc')->get();

        // Add data rows
        $row = 2;
        foreach ($tickets as $ticket) {
            // Calculate durations in minutes
            $waitingDuration = $ticket->in_progress_at 
                ? $ticket->created_at->diffInMinutes($ticket->in_progress_at) 
                : 0;

            $processingDuration = ($ticket->in_progress_at && $ticket->closed_at)
                ? $ticket->in_progress_at->diffInMinutes($ticket->closed_at)
                : 0;

            $confirmationDuration = ($ticket->closed_at && $ticket->user_confirmed_at)
                ? $ticket->closed_at->diffInMinutes($ticket->user_confirmed_at)
                : 0;

            $totalDuration = $ticket->created_at->diffInMinutes($ticket->user_confirmed_at);

            // Calculate performance score (1 if processing time <= 60 minutes, 0 if > 60 minutes)
            $performanceScore = 0; // Default to 0
            if ($ticket->in_progress_at && $ticket->closed_at) {
                $processingTime = $ticket->in_progress_at->diffInMinutes($ticket->closed_at);
                $performanceScore = $processingTime <= 60 ? 1 : 0;
            }

            // Get admin notes from the last admin response
            $adminNotes = '';
            if ($ticket->admin_responses) {
                $responses = json_decode($ticket->admin_responses, true);
                if (is_array($responses) && !empty($responses)) {
                    $lastResponse = end($responses);
                    $adminNotes = $lastResponse['notes'] ?? '';
                }
            }

            // Get confirmation notes
            $confirmationNotes = '';
            if ($ticket->user_replies) {
                $replies = json_decode($ticket->user_replies, true);
                if (is_array($replies) && !empty($replies)) {
                    $lastReply = end($replies);
                    if (isset($lastReply['type']) && $lastReply['type'] === 'confirm') {
                        $confirmationNotes = $lastReply['notes'] ?? '';
                    }
                }
            }

            $data = [
                $ticket->ticket_number,
                $ticket->user->name,
                $ticket->category,
                $ticket->department,
                ucfirst($ticket->priority),
                ucfirst($ticket->status),
                $ticket->created_at->format('d/m/Y'),
                $ticket->created_at->format('H:i'),
                $ticket->in_progress_at ? $ticket->in_progress_at->format('H:i') : '-',
                $ticket->closed_at ? $ticket->closed_at->format('H:i') : '-',
                $ticket->user_confirmed_at ? $ticket->user_confirmed_at->format('H:i') : '-',
                $ticket->description,
                $adminNotes,
                $confirmationNotes,
                $totalDuration,
                $performanceScore
            ];

            foreach ($data as $index => $value) {
                $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue($columnLetter . $row, $value);
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'P') as $column) {
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