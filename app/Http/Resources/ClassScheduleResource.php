<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'mata_kuliah'         => $this->mata_kuliah,
            'kelas'               => $this->kelas,
            'dosen'               => $this->dosen,
            'media_pembelajaran'  => $this->media_pembelajaran,
            'sks'                 => $this->sks,
            'sesi'                => $this->sesi,
            'hari'                => $this->hari,
            'waktu_mulai'         => $this->waktu_mulai,
            'waktu_selesai'       => $this->waktu_selesai,
            'waktu_lengkap'       => $this->waktu_lengkap,
            'ruangan'             => $this->ruangan,
            'is_active'           => $this->is_active,
            'semester'            => $this->semester,
            'assignments'         => ClassAssignmentResource::collection($this->whenLoaded('assignments')),
            'assignments_count'   => $this->whenCounted('assignments'),
            'created_at'          => $this->created_at->toIso8601String(),
        ];
    }
}
