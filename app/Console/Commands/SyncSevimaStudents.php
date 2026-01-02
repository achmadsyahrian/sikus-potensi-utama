<?php

namespace App\Console\Commands;

use App\Models\StudentDetail;
use App\Services\Sevima\MahasiswaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSevimaStudents extends Command
{
    protected $signature = 'sync:sevima-students';
    protected $description = 'Syncs student data from Sevima API to local users and student_details tables.';
    protected $mahasiswaService;

    public function __construct(MahasiswaService $mahasiswaService)
    {
        parent::__construct();
        $this->mahasiswaService = $mahasiswaService;
    }

    public function handle()
    {
        $this->info("🚀 Starting Sevima Student synchronization...");
        $currentPage = 1;
        $syncedCount = 0;
        $syncStartTime = now();
        
        // =================================================================
        // === COUNTER BARU UNTUK BATCH REQUEST ===
        // =================================================================
        $requestsInBatch = 0;
        $batchLimit = 50; // Batas request sebelum jeda

        StudentDetail::query()->update(['synced_at' => null]);
        $this->info("All existing student details marked for potential deletion.");

        do {
            $this->line("Fetching page {$currentPage}...");
            $sevimaResponse = $this->mahasiswaService->getAllStudents($currentPage);
            
            // Tambah counter setiap kali kita mencoba fetch data
            $requestsInBatch++;

            if (empty($sevimaResponse) || empty($sevimaResponse['data'])) {
                $this->warn('No more data from Sevima API. Sync process will now finalize.');
                break;
            }

            $sevimaStudents = $sevimaResponse['data'];
            $lastPage = $sevimaResponse['meta']['last_page'] ?? $currentPage;
            
            $progressBar = $this->output->createProgressBar(count($sevimaStudents));
            $progressBar->start();

            foreach ($sevimaStudents as $sevimaStudentItem) {
                try {
                    $this->mahasiswaService->syncUserAndStudentDetail($sevimaStudentItem, $syncStartTime);
                    $syncedCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to sync student data.', [
                        'student_data' => $sevimaStudentItem,
                        'error' => $e->getMessage()
                    ]);
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->info("\nPage {$currentPage} of {$lastPage} processed. (Batch count: {$requestsInBatch}/{$batchLimit})");
            
            $currentPage++;

            // =================================================================
            // === LOGIKA BARU UNTUK JEDA PER BATCH ===
            // =================================================================
            // Jika kita belum di halaman terakhir DAN counter batch sudah mencapai limit
            if ($currentPage <= $lastPage && $requestsInBatch >= $batchLimit) {
                $this->warn("Batch limit reached ({$batchLimit} requests). Pausing for 60 seconds to cool down...");
                sleep(60); // Jeda selama 1 menit
                $requestsInBatch = 0; // Reset counter
                $this->info("Continuing synchronization...");
            }

        } while ($currentPage <= $lastPage);

        $this->info("Cleaning up old student records...");
        $deletedCount = StudentDetail::whereNull('synced_at')->delete();
        $this->info("✅ Cleaned up {$deletedCount} old student detail records.");

        $this->info("🎉 Synchronization finished. {$syncedCount} students synced successfully.");

        return Command::SUCCESS;
    }
}