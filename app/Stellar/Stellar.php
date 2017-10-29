<?php

namespace App\Stellar;

use GuzzleHttp\Client;

class Stellar
{
    const HORIZON = 'https://horizon.stellar.org';

    const STELLAR_JS = '/Users/mprom/Code/interstellar/public/stellar.js';
    const EXEC = '/usr/local/bin/node ' . self::STELLAR_JS;

    /**
     * @var Client
     */
    protected $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    /**
     * Generates new Keypair
     *
     * @return object with public_key and secret_key
     */
    public function generateKeypair()
    {
        $data = exec(implode(' ', [
            self::EXEC,
            'generate',
        ]));

        return json_decode($data);
    }

    /**
     * Gets account data from Horizon
     *
     * @param $publicKey
     * @return mixed|null
     */
    public function accountDetails($publicKey)
    {
        $response = $this->http->get(sprintf(
            self::HORIZON,
            '/accounts/',
            $publicKey
        ));

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Returns public key of the account's creator
     *
     * @param $publicKey
     */
    public function accountCreator($publicKey)
    {
        $response = $this->http->get(sprintf(
            self::HORIZON,
            '/accounts',
            "/{$publicKey}",
            '/payments'
        ));

        $data = json_decode((string)$response->getBody(), true);
        $firstPayment = $data['_embedded']['records'][0];

        return $firstPayment['source_account'];
    }

    /**
     * Adds signer to Stellar account
     *
     * @param string $signer public key of new signer
     * @param string $secretKey secret key of account
     * @param int $weight
     * @return mixed
     */
    public function addSignerToAccount($signer, $secretKey, $weight = 1)
    {
        return $this->submit($secretKey, 'addSigner', [
            'signer' => $signer,
            'weight' => $weight,
        ]);
    }

    /**
     * Submits transaction using node process and returns the output
     *
     * @param $secretKey
     * @param $action
     * @param $data
     * @return mixed
     */
    public function submit($secretKey, $action, $data)
    {
        $command = implode(' ', [
            self::EXEC,
            escapeshellarg(json_encode([
                'action' => $action,
                'secret' => $secretKey,
                'data'   => $data,
            ])),
        ]);

        $response = trim(shell_exec($command));

        return json_decode($response, true);
    }
}