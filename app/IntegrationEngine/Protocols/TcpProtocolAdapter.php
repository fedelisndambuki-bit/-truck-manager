<?php

namespace App\IntegrationEngine\Protocols;

use Socket;
use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tanaki\IntegrationContracts\Exception\DeviceOfflineException;

/**
 * Real TCP/IP client built on ext-sockets (confirmed loaded in this
 * environment). Suitable for weighbridge indicators and GPS units that
 * expose a raw TCP port rather than an HTTP/MQTT API.
 */
class TcpProtocolAdapter implements ProtocolAdapterInterface
{
    protected ?Socket $socket = null;

    protected int $readTimeoutSeconds = 5;

    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::TCP);
    }

    public function connect(array $config): bool
    {
        $host = $config['host'] ?? null;
        $port = $config['port'] ?? null;
        $this->readTimeoutSeconds = $config['read_timeout'] ?? 5;

        if (! $host || ! $port) {
            return false;
        }

        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($socket === false) {
            return false;
        }

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->readTimeoutSeconds, 'usec' => 0]);

        if (! @socket_connect($socket, $host, (int) $port)) {
            socket_close($socket);

            return false;
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

        return socket_write($this->socket, $bytes, strlen($bytes));
    }

    public function receive(): mixed
    {
        $this->assertConnected();

        $data = socket_read($this->socket, 4096, PHP_NORMAL_READ);

        return $data === false ? null : $data;
    }

    /**
     * Long-running loop — intended to run inside a dedicated Artisan
     * daemon command, not inside a web request.
     */
    public function listen(callable $onDataReceived): void
    {
        $this->assertConnected();

        while ($this->isConnected()) {
            $data = @socket_read($this->socket, 4096, PHP_NORMAL_READ);

            if ($data === false || $data === '') {
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
            throw DeviceOfflineException::forDevice('tcp-adapter', 'Not connected — call connect() first.');
        }
    }
}
