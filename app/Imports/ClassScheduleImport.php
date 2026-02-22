<?php

namespace App\Imports;

use App\Models\ClassSchedule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ClassScheduleImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row['hari']) || empty($row['mata_kuliah'])) {
                continue;
            }

            // Parse waktu — support format "08:00-10:00" or "08:00"
            $waktuMulai   = null;
            $waktuSelesai = null;

            $waktu = trim($row['waktu'] ?? '');
            if (str_contains($waktu, '-')) {
                [$waktuMulai, $waktuSelesai] = explode('-', $waktu, 2);
                $waktuMulai   = trim($waktuMulai);
                $waktuSelesai = trim($waktuSelesai);
            } elseif ($waktu) {
                $waktuMulai = $waktu;
            }

            // Normalize hari
            $hariMap = [
                'senin'  => 'Senin',  'monday'    => 'Senin',
                'selasa' => 'Selasa', 'tuesday'   => 'Selasa',
                'rabu'   => 'Rabu',   'wednesday' => 'Rabu',
                'kamis'  => 'Kamis',  'thursday'  => 'Kamis',
                'jumat'  => 'Jumat',  'friday'    => 'Jumat',
                'sabtu'  => 'Sabtu',  'saturday'  => 'Sabtu',
                'minggu' => 'Minggu', 'sunday'    => 'Minggu',
            ];
            $hariRaw = strtolower(trim($row['hari'] ?? ''));
            $hari    = $hariMap[$hariRaw] ?? ucfirst($hariRaw);

            ClassSchedule::create([
                'user_id'           => $this->userId,
                'mata_kuliah'       => trim($row['mata_kuliah'] ?? ''),
                'kelas'             => trim($row['kelas'] ?? ''),
                'dosen'             => trim($row['dosen'] ?? ''),
                'media_pembelajaran' => trim($row['media_pembelajaran'] ?? ''),
                'sks'               => (int) ($row['sks'] ?? 2),
                'sesi'              => isset($row['sesi']) ? (int) $row['sesi'] : null,
                'hari'              => $hari,
                'waktu_mulai'       => $waktuMulai ?: '00:00',
                'waktu_selesai'     => $waktuSelesai,
                'is_active'         => true,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'hari'        => 'required',
            'mata_kuliah' => 'required',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }
}
