<?php

namespace App\Http\Resources\Api;

/**
 * Mobile/API contract for tickets (unified Pengajuan).
 * Delegates to PengajuanResource for canonical fields; keeps legacy aliases.
 */
class TicketResource extends PengajuanResource
{
}
