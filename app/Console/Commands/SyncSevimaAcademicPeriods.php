<?php

namespace App\Console\Commands;

use App\Models\AcademicPeriod;
use App\Services\Sevima\AcademicPeriodService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncSevimaAcademicPeriods extends Command
{
    protected $signature = 'sync:sevima-academic-periods';
    protected $description = 'Syncs academic periods from Sevima API to local database.';

    protected $academicPeriodService;

    public function __construct(AcademicPeriodService $academicPeriodService)
    {
        parent::__construct();
        $this->academicPeriodService = $academicPeriodService;
    }

    public function handle()
    {
        $this->info("Starting Sevima Academic Periods synchronization...");
        $currentPage = 1;
        $lastPage = 1;
        $syncedCount = 0;
        $totalSevimaRecords = 0;
        do {
            $this->info("Fetching page {$currentPage}...");
            $sevimaResponse = $this->academicPeriodService->fetchAcademicPeriods(['page' => $currentPage, 'o-id' => 'asc']);
            
            if (empty($sevimaResponse) || empty($sevimaResponse['data'])) {
                $this->warn('No data found from Sevima API for this page. Aborting sync.');
                break;
            }

            $sevimaPeriods = $sevimaResponse['data'];
            $lastPage = $sevimaResponse['meta']['last_page'] ?? $currentPage;
            $totalSevimaRecords = $sevimaResponse['meta']['total'] ?? $totalSevimaRecords;

            DB::transaction(function () use ($sevimaPeriods, &$syncedCount) {
                foreach ($sevimaPeriods as $sevimaPeriod) {
                    try {
                        $attributes = $sevimaPeriod['attributes'];
                        $semester = null;
                        if (Str::contains($attributes['nama_periode'], 'Ganjil', true)) {
                            $semester = 'Ganjil';
                        } elseif (Str::contains($attributes['nama_periode'], 'Genap', true)) {
                            $semester = 'Genap';
                        } elseif (Str::contains($attributes['nama_periode'], 'Pendek', true)) {
                            $semester = 'Pendek';
                        }

                        $academicPeriodData = [
                            'name' => $attributes['nama_periode'],
                            'academic_year' => $attributes['tahun_ajar'],
                            'semester' => $semester,
                            'start_date' => empty($attributes['tanggal_awal']) ? null : $attributes['tanggal_awal'],
                            'end_date' => empty($attributes['tanggal_akhir']) ? null : $attributes['tanggal_akhir'],
                            'is_active' => $attributes['is_aktif'] === '1' ? true : false,
                            'last_synced_at' => now(),
                        ];

                        // Log::info("Syncing academic period: " . json_encode($academicPeriodData)); // for debugging
                        AcademicPeriod::updateOrCreate(
                            ['academic_year' => $attributes['tahun_ajar'], 'semester' => $semester],
                            $academicPeriodData
                        );

                        $syncedCount++;
                    } catch (\Exception $e) {
                        Log::error('Error syncing academic period: ' . $e->getMessage(), ['data' => $sevimaPeriod]);
                    }
                }
            });

            $currentPage++;
            sleep(2);
        } while ($currentPage <= $lastPage);

        $this->info("Synchronization finished. {$syncedCount} academic periods synced successfully out of {$totalSevimaRecords} total records from Sevima.");

        return Command::SUCCESS;
    }
}
