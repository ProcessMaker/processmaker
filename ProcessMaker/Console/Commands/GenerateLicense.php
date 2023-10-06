<?php

namespace ProcessMaker\Console\Commands;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;

class GenerateLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:generate-license {token?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->argument('token')) {
            $this->decode();

            return;
        }

        $packages = [
            'processmaker/package-collections',
            'processmaker/package-savedsearch',
            'processmaker/package-data-sources',
        ];

        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm = new Sha256();
        $signingKey = InMemory::plainText(random_bytes(32));

        $now = new DateTimeImmutable();
        $token = $tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('http://license-server.com')
            // Configures the audience (aud claim)
            ->permittedFor('http://this-instance.com')
            // Configures the id (jti claim)
            ->identifiedBy('123ABC')
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            // ->canOnlyBeUsedAfter($now->modify('+1 minute'))
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+1 hour'))
            // Configures a new claim, called "uid"
            ->withClaim('pm-packages', $packages)
            ->withClaim('pm-version', '^4.8')
            // Configures a new header, called "foo"
            // ->withHeader('foo', 'bar')
            // Builds a new token
            ->getToken($algorithm, $signingKey);

        $this->info($token->toString());
    }

    public function decode()
    {
        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse((string) $this->argument('token'));
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            echo 'Oh no, an error: ' . $e->getMessage();
        }
        assert($token instanceof UnencryptedToken);

        $this->info($token->claims()->get('pm-version'));
        $this->info(print_r($token->claims()->get('pm-packages'), true));
    }
}
