<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
    ) {}

    private function itQuery()
    {
        return Pengajuan::sirs();
    }

    public function update(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|in:open,in_progress,pending_confirmation,confirmed,rejected',
        ]);

        $data = [];
        if (isset($validated['notes'])) {
            $this->service->addMessage(auth()->user(), $ticket, $validated['notes'], []);
        }
        if (isset($validated['status'])) {
            $ticket->update(['status' => $validated['status']]);
        }

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket has been updated successfully.');
    }

    public function updateStatus(Request $request, Pengajuan $ticket)
    {
        $status = $request->status;
        $ticket->update(['status' => $status]);
        return redirect()->back()->with('success', 'Status ticket berhasil diperbarui.');
    }

    public function respond(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'status' => 'required|in:in_progress,pending_confirmation,confirmed,rejected',
        ]);

        $this->service->addMessage(auth()->user(), $ticket, $validated['notes'], $request->file('photo') ? [$request->file('photo')] : []);

        $ticket->update(['status' => $validated['status']]);

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Response berhasil ditambahkan.');
    }
}
