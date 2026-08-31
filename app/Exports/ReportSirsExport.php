<?php

namespace App\Exports;

use App\Models\Ticket;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class ReportSirsExport
{
    protected $dateFrom;
    protected $dateTo;
    protected $year;
    protected $selectedIds;
    protected $categoryId;

    public function __construct($dateFrom = null, $dateTo = null, $year = null, array $selectedIds = [], $categoryId = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->year = $year;
        $this->categoryId = $categoryId;
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
            'Waktu Proses (Menit)',
            'Skor Kinerja',
            'Persentase Kinerja (%)'
        ];

        // Style the headers
        $sheet->getStyle('A1:R1')->applyFromArray([
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
        $query = Ticket::with(['user']);

        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        } else {
            if ($this->dateFrom) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            }
            if ($this->year) {
                $query->whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$this->year]);
            }
            if ($this->categoryId) {
                $query->where('category_id', $this->categoryId);
            }
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        // Variables for performance calculation
        $totalProcessedTickets = $tickets->filter(function($ticket) {
            return $ticket->status === 'closed' || $ticket->status === 'confirmed';
        })->count();
        
        $ticketsUnder60Minutes = $tickets->filter(function($ticket) {
            return ($ticket->status === 'closed' || $ticket->status === 'confirmed') && 
                   $ticket->in_progress_at && 
                   $ticket->closed_at && 
                   $ticket->in_progress_at->diffInMinutes($ticket->closed_at) <= 60;
        })->count();
        
        $performancePercentage = $totalProcessedTickets > 0 
            ? round(($ticketsUnder60Minutes / $totalProcessedTickets) * 100, 2) 
            : 0;
            
        $closedTickets = $tickets->filter(function($ticket) {
            return ($ticket->status === 'closed' || $ticket->status === 'confirmed') && 
                   $ticket->in_progress_at && 
                   $ticket->closed_at;
        });
        
        $avgCompletionTime = 0;
        if ($closedTickets->count() > 0) {
            $totalTime = $closedTickets->sum(function($ticket) {
                return $ticket->in_progress_at->diffInMinutes($ticket->closed_at);
            });
            $avgCompletionTime = round($totalTime / $closedTickets->count());
        }

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

            $totalDuration = $ticket->created_at->diffInMinutes($ticket->user_confirmed_at ?? now());

            // Calculate performance score
            $performanceScore = 0;
            if ($ticket->status === 'closed' && $ticket->in_progress_at && $ticket->closed_at) {
                if ($processingDuration <= 60) {
                    $performanceScore = 1;
                }
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
                $processingDuration,
                $performanceScore,
                $performancePercentage
            ];

            foreach ($data as $index => $value) {
                $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue($columnLetter . $row, $value);
            }
            $row++;
        }

        // Add summary row
        $row++;
        $sheet->setCellValue('A' . $row, 'Ringkasan Kinerja:');
        $sheet->setCellValue('B' . $row, "Total Tiket Selesai: {$totalProcessedTickets}");
        $sheet->setCellValue('C' . $row, "Tiket <= 60 Menit: {$ticketsUnder60Minutes}");
        $sheet->setCellValue('D' . $row, "Persentase: {$performancePercentage}%");
        $sheet->setCellValue('E' . $row, "Rata-rata Waktu: {$avgCompletionTime} Menit");

        // Style summary row
        $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFE699'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'R') as $column) {
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