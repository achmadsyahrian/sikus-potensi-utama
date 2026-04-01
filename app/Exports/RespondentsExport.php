<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RespondentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $questionnaireId;

    public function __construct($questionnaireId)
    {
        $this->questionnaireId = $questionnaireId;
    }

    public function collection()
    {
        // Query Internal
        $internal = DB::table('answers')
            ->join('users', 'answers.user_id', '=', 'users.id')
            ->leftJoin('roles', 'answers.role_id', '=', 'roles.id')
            ->leftJoin('student_details', 'users.id', '=', 'student_details.user_id')
            ->leftJoin('lecturer_details', 'users.id', '=', 'lecturer_details.user_id')
            ->where('answers.questionnaire_id', $this->questionnaireId)
            ->whereNotNull('answers.user_id')
            ->select(
                'users.name', 'roles.name as role_name',
                'student_details.nim', 'lecturer_details.nidn',
                DB::raw('NULL as company'), 'answers.created_at'
            )->distinct();

        // Query External
        $external = DB::table('answers')
            ->join('respondent_externals', 'answers.respondent_external_id', '=', 'respondent_externals.id')
            ->where('answers.questionnaire_id', $this->questionnaireId)
            ->whereNotNull('answers.respondent_external_id')
            ->select(
                'respondent_externals.name', 'respondent_externals.role as role_name',
                DB::raw('NULL as nim'), DB::raw('NULL as nidn'),
                'respondent_externals.company_or_institution as company', 'answers.created_at'
            )->distinct();

        return $internal->union($external)->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nama Responden',
            'Peran',
            'NIM / NIDN',
            'Instansi / Perusahaan',
            'Tanggal Mengisi'
        ];
    }

    public function map($row): array
    {
        static $no = 1;

        $role = $row->role_name;
        if($role === 'alumni') $role = 'Alumni';
        if($role === 'mitra') $role = 'Mitra Kerjasama';
        if($role === 'pengguna_lulusan') $role = 'Pengguna Lulusan';

        return [
            $no++,
            ucwords(strtolower($row->name)),
            $role,
            $row->nim ?: ($row->nidn ?: '-'),
            $row->company ?: '-',
            date('d/m/Y H:i', strtotime($row->created_at))
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
