<div class="mt-6">
    <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Information Request History</h3>

    @if ($threads->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No back-and-forth yet.</p>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($threads as $entry)
                <div @class([
                    'rounded-lg border px-4 py-3 text-sm',
                    'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-700' => $entry->author_type === 'superadmin',
                    'bg-primary-50 border-primary-200 dark:bg-primary-900/20 dark:border-primary-800' => $entry->author_type === 'applicant',
                ])>
                    <div class="mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $entry->author_type === 'superadmin' ? ($entry->author?->name ?? 'Super Admin') : 'Applicant' }}
                        · {{ $entry->created_at->format('d M Y, H:i') }}
                    </div>
                    <div class="text-gray-900 dark:text-white">{{ $entry->message }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
