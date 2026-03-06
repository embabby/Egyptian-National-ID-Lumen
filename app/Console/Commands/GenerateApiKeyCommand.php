<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;

class GenerateApiKeyCommand extends Command
{
    protected $signature = 'api-key:generate {--name= : Optional name for the key}';

    protected $description = 'Generate a new API key for service-to-service authentication';

    public function handle(): int
    {
        $name = $this->option('name') ?: 'Key ' . now()->toDateTimeString();
        $plainKey = 'eg_' . bin2hex(random_bytes(24));

        ApiKey::create([
            'name' => $name,
            'key_hash' => hash('sha256', $plainKey),
            'is_active' => true,
        ]);

        $this->info('API key created successfully.');
        $this->line('Key (store securely, shown once): <comment>' . $plainKey . '</comment>');
        $this->line('Use header: X-API-Key: ' . $plainKey);

        return self::SUCCESS;
    }
}
