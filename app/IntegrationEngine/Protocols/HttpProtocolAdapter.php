<?php

namespace App\IntegrationEngine\Protocols;

use Illuminate\Support\Facades\Http;
use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tanaki\IntegrationContracts\Exception\DeviceOfflineException;

/**
 * Handles both HTTP and HTTPS — one class, not two, since Laravel's Http
 * client (and the underlying cURL) already differentiates purely by URL
 * scheme; a second "HttpsProtocolAdapter" would just be duplicated code.
 *
 * This adapter is for OUTBOUND HTTP — a driver polling a vendor's REST API
 * (e.g. a GPS tracker's cloud API). Inbound webhook-style push (a device
 * POSTing to us) is a different architectural concern — a controller route
 * receives the request and calls the driver's parse method directly; it
 * doesn't go through listen() here, which is why listen() is intentionally
 * unsupported on this adapter.
 */
class HttpProtocolAdapter implements ProtocolAdapterInterface
{
    protected ?string $baseUrl = null;

    protected array $headers = [];

    protected int $timeoutSeconds = 5;

    protected bool $connected = false;

    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::HTTP);
    }

    public function connect(array $config): bool
    {
        $this->baseUrl = $config['base_url'] ?? null;
        $this->headers = $config['headers'] ?? [];
        $this->timeoutSeconds = $config['timeout'] ?? 5;

        if (! $this->baseUrl) {
            $this->connected = false;

            return false;
        }

        try {
            Http::withHeaders($this->headers)->timeout($this->timeoutSeconds)->get($this->baseUrl);
            $this->connected = true;
        } catch (\Throwable) {
            $this->connected = false;
        }

        return $this->connected;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->baseUrl = null;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function send(array|string $payload): mixed
    {
        if (! $this->baseUrl) {
            throw DeviceOfflineException::forDevice($this->baseUrl ?? 'http-adapter', 'No base URL configured — call connect() first.');
        }

        $body = is_array($payload) ? $payload : ['data' => $payload];

        return Http::withHeaders($this->headers)
            ->timeout($this->timeoutSeconds)
            ->post($this->baseUrl, $body)
            ->json();
    }

    public function receive(): mixed
    {
        if (! $this->baseUrl) {
            throw DeviceOfflineException::forDevice($this->baseUrl ?? 'http-adapter', 'No base URL configured — call connect() first.');
        }

        return Http::withHeaders($this->headers)
            ->timeout($this->timeoutSeconds)
            ->get($this->baseUrl)
            ->json();
    }

    public function listen(callable $onDataReceived): void
    {
        throw new \RuntimeException(
            'HttpProtocolAdapter is request/response only. Push-style HTTP ingestion (a device POSTing to us) is handled by a webhook route calling the driver directly, not by this adapter.'
        );
    }

    public function getRequiredDependencies(): array
    {
        return ['ext-curl (installed)'];
    }
}
