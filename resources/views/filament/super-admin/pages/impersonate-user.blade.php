<x-filament-panels::page>
    <div class="space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Select any user account to impersonate them. You will be logged in as that user and redirected to their admin panel.
            A warning banner with a <strong>Stop Impersonating</strong> button will appear at the top of every page. All sessions are logged.
        </p>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
