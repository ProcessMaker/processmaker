<?php

namespace ProcessMaker\Console\Commands;

use CodeGreenCreative\SamlIdp\Console\CreateCertificate;

class CreateSamlCertificate extends CreateCertificate
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'samlidp:cert
                            {--days=7300 : Number of days to add from today as the expiration date}
                            {--keyname=key.pem : Full name of the certificate key file}
                            {--certname=cert.pem : Full name to the certificate file}
                            {--subject= : Set subject of request or cert '
                                . '(e.g. /C=US/ST=New York/L=New York City/O=Example Inc/CN=example.com)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new certificate and private key for your IdP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storagePath = storage_path('samlidp');
        $days = $this->option('days');
        $keyName = $this->option('keyname');
        $certName = $this->option('certname');
        $subject = $this->option('subject');

        // Create storage/samlidp directory
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $key = sprintf('%s/%s', $storagePath, $keyName);
        $cert = sprintf('%s/%s', $storagePath, $certName);
        $question = 'The name chosen for the PEM files already exist. Would you like to overwrite existing PEM files?';

        if ((!file_exists($key) && !file_exists($cert)) || $this->confirm($question)) {
            $command = 'openssl req -x509 -sha256 -nodes -days %s -newkey rsa:2048 -keyout %s -out %s';
            $args = [$days, $key, $cert];

            if (!is_null($subject)) {
                $command .= ' -subj "%s"';
                $args[] = $subject;
            }

            exec(vsprintf($command, $args));
        }
    }
}
