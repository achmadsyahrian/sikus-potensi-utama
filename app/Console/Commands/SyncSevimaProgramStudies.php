<?php

namespace App\Console\Commands;

use App\Models\ProgramStudy;
use App\Services\Sevima\ProgramStudyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSevimaProgramStudies extends Command
{
    protected $signature = 'sync:sevima-program-studies';
    protected $description = 'Syncs program studies from Sevima API to local database.';

    protected $programStudyService;

    public function __construct(ProgramStudyService $programStudyService)
    {
        parent::__construct();
        $this->programStudyService = $programStudyService;
    }

    public function handle()
    {
        $this->info("Starting Sevima Program Studies synchronization...");
        $sevimaResponse = $this->programStudyService->fetchProgramStudies(['o-id' => 'asc']);

        if (empty($sevimaResponse) || empty($sevimaResponse['data'])) {
            $this->warn('No data found from Sevima API. Aborting sync.');
            return Command::FAILURE;
        }

        $sevimaProgramStudies = $sevimaResponse['data'];
        $syncedCount = 0;

        DB::transaction(function () use ($sevimaProgramStudies, &$syncedCount) {
            foreach ($sevimaProgramStudies as $sevimaProgramStudy) {
                try {
                    $attributes = $sevimaProgramStudy['attributes'];
                    
                    $programStudyData = [
                        'program_study_code' => $attributes['kode_program_studi'],
                        'name' => $attributes['nama_program_studi'],
                        'faculty_code' => $attributes['id_fakultas'],
                        'degree_level' => $attributes['id_jenjang'],
                        'is_active' => $attributes['is_aktif'] === '1' ? true : false,
                        'last_synced_at' => now(),
                    ];
                    
                    // Log::info("Syncing program study: " . json_encode($programStudyData)); //for debugging
                    ProgramStudy::updateOrCreate(
                        ['program_study_code' => $attributes['kode_program_studi']],
                        $programStudyData
                    );

                    $syncedCount++;
                } catch (\Exception $e) {
                    Log::error('Error syncing program study: ' . $e->getMessage(), ['data' => $sevimaProgramStudy]);
                }
            }
        });

        $this->info("Synchronization finished. {$syncedCount} program studies synced successfully.");

        return Command::SUCCESS;
    }
}
