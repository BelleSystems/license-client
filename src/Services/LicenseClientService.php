<?php

namespace Bellesoft\LicenseClient\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Bellesoft\LicenseClient\Traits\ResponseTrait;
use Exception;
class LicenseClientService
{

    use ResponseTrait;
    public $license;

    private $licenseKey;
    private $accessToken;

    public function __construct(string $licenseKey)
    {
        $this->licenseKey = $licenseKey;
        $this->accessToken = $this->getAccessToken($licenseKey);
    }

    
    /**
     * Check license status
     *
     * @param string $licenseKey
     * @param array $data
     *
     * @return boolean
     */
    public function validateLicense(array $data = []): bool
    {
        if ($this->accessToken) {
            $url = Config::get('license-client.license_server_url') . '/api/licenses/validate';

            $response = Http::withHeaders([
                'x-host' => Config::get('app.url'),
                'x-host-name' => Config::get('app.name'),
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $data);

            if ($response->ok()) {
                $license = $response->json();

                $this->license = $license;

                return $license && $license['status'] == 'active';
            }
        }

        return false;
    }

    /**
     * Get access token for the given domain
     *
     * @param string $licenseKey
     *
     * @return string
     */
    private function getAccessToken(string $licenseKey): null | string
    {
        $accessTokenCacheKey = $this->getAccessTokenKey($licenseKey);

        $accessToken = Cache::get($accessTokenCacheKey, null);

        if ($accessToken) {
            return $accessToken;
        }

        $url = Config::get('license-client.license_server_url') . '/api/licenses/tokens';

        $response = Http::withHeaders([
            'x-host' => Config::get('app.url'),
            'x-host-name' => Config::get('app.name'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, [
            'license_key' => $licenseKey
        ]);

        $data = $response->json();

        if ($response->ok()) {
            if ($data['status'] === true) {
                if (!empty($data['access_token'])) {
                    $accessToken = $data['access_token'];

                    Cache::put($accessTokenCacheKey, $accessToken, now()->addMinutes(60));

                    return $accessToken;
                } else {
                    return $this->errorResponse($data['message']);
                }
            }
        }

        return $this->errorResponse($data['message']);
    }

    
    /**
     * Get access token cache key
     *
     * @return string
     */
    private function getAccessTokenKey(string $licenseKey): string
    {
        return "license-client:access-token-{$licenseKey}";
    }
}