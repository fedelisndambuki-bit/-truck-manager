<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tanaki\IntegrationContracts\Exception\DeviceOfflineException;
use WebSocket\Client as WebSocketClient;
use WebSocket\ConnectionException;

class WebSocketProtocolAdapter implements ProtocolAdapterInterface
{
    protected ?WebSocketClient $client = null;

    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::WEBSOCKET);
    }

    public function connect(array $config): bool
    {
        $uri = $config['uri'] ?? null;

        if (! $uri) {
            return false;
        }

        try {
            $this->client = new WebSocketClient($uri, [
                'timeout' => $config['timeout'] ?? 5,
            ]);

            return true;
        } catch (\Throwable) {
            $this->client = null;

            return false;
        }
    }

    public function disconnect(): void
    {
        $this->client?->close();
        $this->client = null;
    }

    public function isConnected(): bool
    {
        return $this->client?->isConnected() ?? false;
    }

    public function send(array|string $payload): mixed
    {
        $this->assertConnected();

        $text = is_array($payload) ? json_encode($payload) : $payload;
        $this->client->text($text);

        return true;
    }

    public function receive(): mixed
    {
        $this->assertConnected();

        try {
            return $this->client->receive();
        } catch (ConnectionException) {
            return null;
        }
    }

    public function listen(callable $onDataReceived): void
    {
        $this->assertConnected();

        while ($this->isConnected()) {
            try {
                $data = $this->client->receive();
            } catch (ConnectionException) {
                break;
            }

            if ($data !== null && $data !== '') {
                $onDataReceived($data);
            }
        }
    }

    public function getRequiredDependencies(): array
    {
        return ['textalk/websocket (installed)'];
    }

    protected function assertConnected(): void
    {
        if (! $this->isConnected()) {
            throw DeviceOfflineException::forDevice('websocket-adapter', 'Not connected — call connect() first.');
        }
    }
}
