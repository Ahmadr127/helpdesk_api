<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketPhoto;
use App\Notifications\TicketRespondedNotification;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|max:5120',
        ]);

        // Get existing admin responses or create new array
        $responses = $ticket->admin_responses ? json_decode($ticket->admin_responses, true) : [];

        // Create new response
        $response = [
            'notes' => $validated['notes'],
            'timestamp' => now()->toDateTimeString(),
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()?->name ?? 'Admin',
        ];

        // Save admin response photo(s) if exists
        $files = $this->normalizeFiles($request);
        $paths = [];
        foreach ($files as $file) {
            $path = $file->store('ticket-responses', 'public');
            $paths[] = $path;
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => 'admin_response',
            ]);
        }
        if (! empty($paths)) {
            $response['photo'] = count($paths) === 1 ? $paths[0] : $paths;
        }

        // Add new response to array
        $responses[] = $response;

        // Update ticket
        $ticket->update([
            'admin_responses' => json_encode($responses),
            'status' => $request->input('status', $ticket->status),
        ]);

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket has been updated successfully.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $status = $request->status;
        $updates = ['status' => $status];

        switch ($status) {
            case 'in_progress':
                $updates['in_progress_at'] = now();
                $message = "Ticket #{$ticket->ticket_number} sedang dalam proses";
                break;
            case 'closed':
                $updates['closed_at'] = now();
                $updates['status'] = 'pending'; // Change to pending when admin closes
                $message = "Ticket #{$ticket->ticket_number} telah ditutup";
                break;
            case 'open':
                $message = "Ticket #{$ticket->ticket_number} telah dibuka kembali";
                break;
            default:
                $message = "Status ticket #{$ticket->ticket_number} telah diperbarui";
        }

        $ticket->update($updates);

        // Send notification to user
        $ticket->user->notify(new TicketRespondedNotification(
            $ticket,
            auth()->user(),
            $message,
            true,
            'updated'
        ));

        return redirect()->back()->with('success', 'Status ticket berhasil diperbarui.');
    }

    public function respond(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|max:5120',
            'status' => 'required|in:in_progress,closed',
        ]);

        $response = [
            'notes' => $validated['notes'],
            'timestamp' => now(),
            'status' => $validated['status'],
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()?->name ?? 'Admin',
        ];

        // Get existing responses or initialize empty array
        $responses = json_decode($ticket->admin_responses, true) ?? [];

        // Handle photo upload if provided
        $files = $this->normalizeFiles($request);
        $paths = [];
        foreach ($files as $file) {
            $path = $file->store('ticket-responses', 'public');
            $paths[] = $path;

            // Create ticket photo record
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => 'admin_response',
            ]);
        }
        if (! empty($paths)) {
            $response['photo'] = count($paths) === 1 ? $paths[0] : $paths;
        }

        // Add new response to array
        $responses[] = $response;

        // Update ticket
        $ticket->update([
            'admin_responses' => json_encode($responses),
            'status' => $validated['status'],
        ]);

        // Send notification to user
        $ticket->user->notify(new TicketRespondedNotification(
            $ticket,
            auth()->user(),
            "Admin telah merespon ticket #{$ticket->ticket_number}",
            true,
            'responded'
        ));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Response berhasil ditambahkan.');
    }

    /**
     * Normalisasi input foto: dukung `photos[]` (banyak, maks 3) atau `photo` (tunggal).
     */
    private function normalizeFiles(Request $request): array
    {
        $files = $request->file('photos') ?? $request->file('photo');
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        } elseif (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        return array_slice(array_values(array_filter($files)), 0, 3);
    }
}
