<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderPerbaikanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nomor' => $this->nomor,
            'tanggal' => $this->tanggal,
            'unit_proses' => $this->unit_proses,
            'unit_proses_name' => $this->unit_proses_name,
            'unit_penerima' => $this->unit_penerima,
            'nama_peminta' => $this->nama_peminta,
            'nip_peminta' => $this->nip_peminta,
            'jenis_barang' => $this->jenis_barang,
            'kode_inventaris' => $this->kode_inventaris,
            'nama_barang' => $this->nama_barang,
            'lokasi' => $this->lokasi,
            'location' => $this->whenLoaded('location'),
            'keluhan' => $this->keluhan,
            'prioritas' => $this->prioritas,
            'status' => $this->status,
            'follow_up' => $this->follow_up,
            'nama_penanggung_jawab' => $this->nama_penanggung_jawab,
            'foto' => $this->foto,
            'foto_url' => $this->foto ? Storage::disk('public')->url($this->foto) : null,
            'creator' => $this->whenLoaded('creator', fn()=> new UserResource($this->creator)),
            'updater' => $this->whenLoaded('updater'),
            'history' => $this->whenLoaded('history', fn()=> $this->history->map(fn($h)=>[
                'id'=>$h->id,
                'status'=>$h->status,
                'keterangan'=>$h->keterangan ?? $h->follow_up ?? null,
                'follow_up'=>$h->follow_up ?? null,
                'creator'=> $h->creator ? ['id'=>$h->creator->id,'name'=>$h->creator->name] : null,
                'created_at'=>$h->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
