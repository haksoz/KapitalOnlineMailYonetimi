<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup
                            {--path= : Yedek dosyasının kaydedileceği dizin (varsayılan: database/backups)}';

    protected $description = 'MySQL/MariaDB veritabanının yedeğini database/backups klasörüne alır.';

    public function handle(): int
    {
        $connection = config('database.default');
        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            $this->error("Yedekleme sadece MySQL/MariaDB için desteklenir. Mevcut bağlantı: {$connection}");

            return self::FAILURE;
        }

        $config = config("database.connections.{$connection}");
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if ($database === '' || $username === '') {
            $this->error('Veritabanı veya kullanıcı adı yapılandırmada eksik.');

            return self::FAILURE;
        }

        $dir = $this->option('path') ?? database_path('backups');
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0755, true)) {
                $this->error("Yedek klasörü oluşturulamadı: {$dir}");

                return self::FAILURE;
            }
        }

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $filepath = rtrim($dir, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . $filename;

        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--lock-tables=false',
            '-h', $host,
            '-P', (string) $port,
            '-u', $username,
            $database,
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $env = $process->getEnv();
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }
        $process->setEnv($env);

        $this->info("Yedek alınıyor: {$filepath}");
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('mysqldump başarısız: ' . $process->getErrorOutput());

            return self::FAILURE;
        }

        if (file_put_contents($filepath, $process->getOutput()) === false) {
            $this->error("Yedek dosyası yazılamadı: {$filepath}");

            return self::FAILURE;
        }

        $this->info("Yedek oluşturuldu: {$filepath}");

        return self::SUCCESS;
    }
}
