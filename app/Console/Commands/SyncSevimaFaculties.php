<?php

namespace App\Console\Commands;

use App\Models\Faculty;
use App\Services\Sevima\FacultyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSevimaFaculties extends Command
{
    protected $signature = 'sync:sevima-faculties';
    protected $description = 'Syncs faculties from Sevima API to local database.';

    protected $facultyService;

    public function __construct(FacultyService $facultyService)
    {
        parent::__construct();
        $this->facultyService = $facultyService;
    }

    public function handle()
    {
        $this->info("Starting Sevima Faculties synchronization...");
        $sevimaResponse = $this->facultyService->fetchFaculties(['o-id' => 'asc']);

        if (empty($sevimaResponse) || empty($sevimaResponse['data'])) {
            $this->warn('No data found from Sevima API. Aborting sync.');
            return Command::FAILURE;
        }

        $sevimaFaculties = $sevimaResponse['data'];
        $syncedCount = 0;

        DB::transaction(function () use ($sevimaFaculties, &$syncedCount) {
            foreach ($sevimaFaculties as $sevimaFaculty) {
                try {
                    $attributes = $sevimaFaculty['attributes'];

                    $facultyData = [
                        'faculty_code' => $attributes['kode_fakultas'],
                        'name' => $attributes['nama'],
                        'is_active' => $attributes['is_aktif'] === '1' ? true : false,
                        'last_synced_at' => now(),
                    ];

                    // Log::info("Syncing faculty: " . json_encode($facultyData)); //for debugging
                    Faculty::updateOrCreate(
                        ['faculty_code' => $attributes['kode_fakultas']],
                        $facultyData
                    );

                    $syncedCount++;
                } catch (\Exception $e) {
                    Log::error('Error syncing faculty: ' . $e->getMessage(), ['data' => $sevimaFaculty]);
                }
            }
        });

        $this->info("Synchronization finished. {$syncedCount} faculties synced successfully.");

        return Command::SUCCESS;
    }
}
