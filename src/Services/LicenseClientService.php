<?php

namespace Bellesoft\LicenseClient\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Bellesoft\LicenseClient\Traits\ResponseTrait;
use Bellesoft\LicenseClient\Exceptions\LicenseValidationException;
use Carbon\Carbon;
class LicenseClientService
{

    use ResponseTrait;
    public $license;

    private $licenseKey;
    private $accessToken;

    public function __construct(string $licenseKey)
    {
        $this->licenseKey = $licenseKey;
        // $this->accessToken = $this->getAccessToken($licenseKey);
        // dd($this->accessToken);
    }

    
    /**
     * Check license status
     *
     * @param array $data
     *
     * @return array License data on success
     *
     * @throws LicenseValidationException When the license server returns an error or the response is invalid
     */
    public function validateLicense(array $data = []): array
    {
        $url = Config::get('license-client.license_server_url') . '/api/licenses/validate';

        $response = Http::withHeaders([
            'x-host' => Config::get('app.url'),
            'x-host-name' => Config::get('app.name'),
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, ['license_key' => $this->licenseKey, ...$data]);

        if (!$response->ok()) {
            throw LicenseValidationException::fromResponse(
                $response->status(),
                $response->json() ?? ['message' => $response->body() ?: 'Unknown license server error.']
            );
        }

        $license = $response->json();
        if (empty($license['data'])) {
            throw new LicenseValidationException(
                502,
                'Invalid license server response: missing data.',
                $license
            );
        }

        $this->license = $license['data'];

        return $this->license;
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

        $url = Config::get('license-client.license_server_url') . '/api/licenses/token';

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
            if ($data['status'] === "success") {
                if (!empty($data['data']) && !empty($data['data']['token'])) {
                    $accessToken = $data['data']['token'];
                    $expiresAt = Carbon::parse($data['data']['expires_at']);

                    Cache::put($accessTokenCacheKey, $accessToken, $expiresAt);

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