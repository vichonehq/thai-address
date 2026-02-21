<?php

namespace Kingw1\ThaiAddress\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncThaiAddresses extends Command
{
    protected $signature = 'thai-addresses:sync
                            {--dry-run : แสดงตัวอย่างข้อมูล 5 แถวแรก ไม่ insert จริง}';

    protected $description = 'ดึงข้อมูลที่อยู่ไทยจาก GitHub เก็บไว้ใน database/data/ แล้ว import ลง DB';

    const SOURCE_URL = 'https://raw.githubusercontent.com/earthchie/jquery.Thailand.js/master/jquery.Thailand.js/database/raw_database/raw_database.json';

    const JSON_PATH = __DIR__ . '/../../database/data/raw_database.json';

    const KEY_MAP = [
        'subdistrict_code' => 'district_code',
        'subdistrict'      => 'district',
        'district_code'    => 'amphoe_code',
        'district'         => 'amphoe',
        'province_code'    => 'province_code',
        'province'         => 'province',
        'postal_code'      => 'zipcode',
    ];

    public function handle(): int
    {
        $this->info('🇹🇭 Thai Addresses Sync');
        $this->newLine();

        // Step 1: Download
        if (!$this->download()) {
            return self::FAILURE;
        }

        $this->newLine();

        // Step 2: Import
        return $this->runImport($this->option('dry-run'));
    }

    private function download(): bool
    {
        $this->info('⬇️  Downloading raw_database.json...');
        $this->line('   Source: ' . self::SOURCE_URL);

        $response = Http::timeout(120)
            ->withHeaders(['Accept-Encoding' => 'gzip'])
            ->get(self::SOURCE_URL);

        if ($response->failed()) {
            $this->error('Download failed — HTTP ' . $response->status());
            return false;
        }

        // สร้าง directory ถ้ายังไม่มี
        $dir = dirname(self::JSON_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(self::JSON_PATH, $response->body());

        $size = round(filesize(self::JSON_PATH) / 1024, 2);
        $this->info("✅ Saved to database/data/raw_database.json ({$size} KB)");

        return true;
    }

    public function runImport(bool $dryRun = false): int
    {
        $this->info('📦 Importing to database...');

        if (!file_exists(self::JSON_PATH)) {
            $this->error('ไม่พบไฟล์ raw_database.json กรุณารัน thai-addresses:sync ก่อน');
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents(self::JSON_PATH), true);

        if (empty($data) || !is_array($data)) {
            $this->error('JSON invalid หรือ empty');
            return self::FAILURE;
        }

        $total = count($data);
        $this->line("   Total records: {$total}");
        $this->line("   Sample keys:   " . implode(', ', array_keys($data[0])));
        $this->newLine();

        // Dry run
        if ($dryRun) {
            $this->warn('-- DRY RUN (ไม่ insert จริง) --');
            foreach (array_slice($data, 0, 5) as $i => $item) {
                $this->line(($i + 1) . '. ' . json_encode($this->mapRow($item), JSON_UNESCAPED_UNICODE));
            }
            return self::SUCCESS;
        }

        // Truncate + Insert
        $this->info('🗑️  Truncating thai_addresses...');
        DB::table('thai_addresses')->truncate();

        $chunks   = array_chunk($data, 500);
        $now      = now();
        $inserted = 0;
        $errors   = 0;

        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            $rows = array_map(fn($item) => $this->mapRow($item, $now), $chunk);

            try {
                DB::table('thai_addresses')->insert($rows);
                $inserted += count($rows);
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error('Insert error: ' . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Done!');
        $this->table(
            ['Total', 'Inserted', 'Errors'],
            [[$total, $inserted, $errors]]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    public function mapRow(array $item, ?\Carbon\Carbon $now = null): array
    {
        $now ??= now();
        $map = self::KEY_MAP;

        return [
            'subdistrict_code' => (string) ($item[$map['subdistrict_code']] ?? ''),
            'subdistrict'      => (string) ($item[$map['subdistrict']]      ?? ''),
            'district_code'    => (string) ($item[$map['district_code']]    ?? ''),
            'district'         => (string) ($item[$map['district']]         ?? ''),
            'province_code'    => (string) ($item[$map['province_code']]    ?? ''),
            'province'         => (string) ($item[$map['province']]         ?? ''),
            'postal_code'      => (string) ($item[$map['postal_code']]      ?? ''),
            'created_at'       => $now,
            'updated_at'       => $now,
        ];
    }
}
