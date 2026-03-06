<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use Illuminate\Database\Seeder;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds. Creates a development API key.
     * The key is only shown once; store it in .env as API_KEY for local use.
     */
    public function run(): void
    {
        $plainKey = 'dev_' . bin2hex(random_bytes(16));

        $apiKey = ApiKey::firstOrCreate(
            ['name' => 'Development Key'],
            [
                'key_hash' => hash('sha256', $plainKey),
                'is_active' => true,
            ]
        );

        if ($apiKey->wasRecentlyCreated && $this->command) {
            $this->command->info('Development API key (save this, it will not be shown again): ' . $plainKey);
        }

        $this->command?->info('Development API key (save this, it will not be shown again): ' . $plainKey);
    }
}
