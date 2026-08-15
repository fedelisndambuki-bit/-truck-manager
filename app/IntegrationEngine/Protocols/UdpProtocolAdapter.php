<?php

namespace App\IntegrationEngine\Protocols;

use Socket;
use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tanaki\IntegrationContracts\Exception\DeviceOfflineException;

class UdpProtocolAdapter implements ProtocolAdapterInterface
{
    protected ?Socket $socket = null;

    protected ?string $host = null;

    protected ?int $port = null;

    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::UDP);
    }

    public function connect(array $config): bool
    {
        $this->host = $config['host'] ?? null;
        $this->port = $config['port'] ?? null;

        if (! $this->host || ! $this->port) {
            return false;
        }

        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            return false;
        }

        if (! empty($config['bind_local'])) {
            @socket_bind($socket, '0.0.0.0', (int) $this->port);
        }

        $this->socket = $socket;

        return true;
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->socket !== null;
    }

    public function send(array|string $payload): mixed
    {
        $this->assertConnected();

        $bytes = is_array($payload) ? json_encode($payload) : $payload;

        return socket_sendto($this->socket, $bytes, strlen($bytes), 0, $this->host, $this->port);
    }

    public function receive(): mixed
    {
        $this->assertConnected();

        $data = '';
        $from = '';
        $port = 0;
        $length = @socket_recvfrom($this->socket, $data, 4096, 0, $from, $port);

        return $length === false ? null : $data;
    }

    public function listen(callable $onDataReceived): void
    {
        $this->assertConnected();

        while ($this->isConnected()) {
            $data = '';
            $from = '';
            $port = 0;
            $length = @socket_recvfrom($this->socket, $data, 4096, 0, $from, $port);

            if ($length === false || $data === '') {
                continue;
            }

            $onDataReceived($data);
        }
    }

    public function getRequiredDependencies(): array
    {
        return ['ext-sockets (installed)'];
    }

    protected function assertConnected(): void
    {
        if (! $this->isConnected()) {
            throw DeviceOfflineException::forDevice('udp-adapter', 'Not connected — call connect() first.');
        }
    }
}
