<?php

namespace App\IntegrationEngine\Discovery;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Tanaki\IntegrationContracts\Attribute\IntegrationDriver;
use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;

/**
 * Scans app/IntegrationEngine/Drivers/**\/*.php for classes tagged with
 * #[IntegrationDriver], mirroring Laravel's own route:cache/config:cache
 * philosophy: scan + cache to a plain PHP array file rather than
 * hand-maintaining a config array of every driver. The service provider
 * loads the cache at boot if present; falls back to a live scan otherwise
 * (local/dev, or before the cache has been built the first time).
 */
class DriverDiscoveryService
{
    protected string $driversPath;

    protected string $cachePath;

    public function __construct(?string $driversPath = null, ?string $cachePath = null)
    {
        $this->driversPath = $driversPath ?? app_path('IntegrationEngine/Drivers');
        $this->cachePath = $cachePath ?? storage_path('framework/cache/integration-engine-drivers.php');
    }

    /**
     * @return array<string, array{class: class-string<DeviceDriverInterface>, category: string, version: string}>
     */
    public function scan(): array
    {
        if (! is_dir($this->driversPath)) {
            return [];
        }

        $discovered = [];

        $finder = (new Finder)->files()->in($this->driversPath)->name('*.php');

        foreach ($finder as $file) {
            $class = $this->classFromFilePath($file->getRealPath());

            if (! $class || ! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->implementsInterface(DeviceDriverInterface::class)) {
                continue;
            }

            $attributes = $reflection->getAttributes(IntegrationDriver::class);

            if (empty($attributes)) {
                continue;
            }

            /** @var IntegrationDriver $tag */
            $tag = $attributes[0]->newInstance();

            $discovered[$tag->vendor] = [
                'class' => $class,
                'category' => $tag->category,
                'version' => $tag->version,
            ];
        }

        return $discovered;
    }

    public function cache(): array
    {
        $discovered = $this->scan();

        File::ensureDirectoryExists(dirname($this->cachePath));
        File::put($this->cachePath, '<?php return '.var_export($discovered, true).';'.PHP_EOL);

        return $discovered;
    }

    public function loadCached(): ?array
    {
        return File::exists($this->cachePath) ? require $this->cachePath : null;
    }

    public function clearCache(): void
    {
        if (File::exists($this->cachePath)) {
            File::delete($this->cachePath);
        }
    }

    /**
     * Derives the fully-qualified class name from a driver file's path,
     * relative to app/IntegrationEngine/Drivers -> App\IntegrationEngine\Drivers.
     */
    protected function classFromFilePath(string $realPath): ?string
    {
        $normalizedDriversPath = str_replace('\\', '/', $this->driversPath);
        $normalizedFilePath = str_replace('\\', '/', $realPath);

        if (! str_starts_with($normalizedFilePath, $normalizedDriversPath)) {
            return null;
        }

        $relative = ltrim(substr($normalizedFilePath, strlen($normalizedDriversPath)), '/');
        $relative = preg_replace('/\.php$/', '', $relative);
        $relativeNamespace = str_replace('/', '\\', $relative);

        return 'App\\IntegrationEngine\\Drivers\\'.$relativeNamespace;
    }
}
