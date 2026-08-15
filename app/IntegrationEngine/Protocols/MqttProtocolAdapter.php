<?php

namespace App\IntegrationEngine\Protocols;

use PhpMqtt\Client\MqttClient;
use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tanaki\IntegrationContracts\Exception\DeviceOfflineException;

class MqttProtocolAdapter implements ProtocolAdapterInterface
{
    protected ?MqttClient $client = null;

    protected ?string $subscribeTopic = null;

    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::MQTT);
    }

    public function connect(array $config): bool
    {
        $host = $config['host'] ?? null;

        if (! $host) {
            return false;
        }

        $this->subscribeTopic = $config['topic'] ?? null;

        try {
            $this->client = new MqttClient(
                host: $host,
                port: $config['port'] ?? 1883,
                clientId: $config['client_id'] ?? null,
            );

            $this->client->connect(useCleanSession: true);

            return true;
        } catch (\Throwable) {
            $this->client = null;

            return false;
        }
    }

    public function disconnect(): void
    {
        $this->client?->disconnect();
        $this->client = null;
    }

    public function isConnected(): bool
    {
        return $this->client?->isConnected() ?? false;
    }

    public function send(array|string $payload): mixed
    {
        $this->assertConnected();

        if (! $this->subscribeTopic) {
            throw new \RuntimeException('No MQTT topic configured to publish to.');
        }

        $message = is_array($payload) ? json_encode($payload) : $payload;
        $this->client->publish($this->subscribeTopic, $message, 0);

        return true;
    }

    public function receive(): mixed
    {
        throw new \RuntimeException('MQTT is push/subscribe-based — use listen() with a callback, not a blocking receive().');
    }

    public function listen(callable $onDataReceived): void
    {
        $this->assertConnected();

        if (! $this->subscribeTopic) {
            throw new \RuntimeException('No MQTT topic configured to subscribe to.');
        }

        $this->client->subscribe($this->subscribeTopic, function (string $topic, string $message) use ($onDataReceived) {
            $onDataReceived($message);
        }, 0);

        $this->client->loop(true);
    }

    public function getRequiredDependencies(): array
    {
        return ['php-mqtt/client (installed)'];
    }

    protected function assertConnected(): void
    {
        if (! $this->isConnected()) {
            throw DeviceOfflineException::forDevice('mqtt-adapter', 'Not connected — call connect() first.');
        }
    }
}
