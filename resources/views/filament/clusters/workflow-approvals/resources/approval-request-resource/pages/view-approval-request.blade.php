@php
    $myAction = $this->myPendingAction();
    $timeline = $this->getTimeline();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z" />',
        'title' => $record->reference_number,
        'subtitle' => $record->title,
    ])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <x-filament::section>
                <x-slot name="heading">Details</x-slot>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Workflow:</span> {{ $record->workflowDefinition?->name }}</div>
                    <div><span class="text-gray-500">Module:</span> {{ $record->workflowDefinition?->module }}</div>
                    <div><span class="text-gray-500">Status:</span>
                        <x-filament::badge :color="match($record->status) { 'Approved' => 'success', 'Rejected', 'Cancelled' => 'danger', 'Escalated' => 'warning', default => 'info' }">
                            {{ $record->status }}
                        </x-filament::badge>
                    </div>
                    <div><span class="text-gray-500">Submitted By:</span> {{ $record->submittedBy?->name ?? '—' }}</div>
                    <div><span class="text-gray-500">Submitted At:</span> {{ optional($record->submitted_at)->format('d M Y H:i') }}</div>
                    <div><span class="text-gray-500">SLA Due:</span> {{ optional($record->sla_due_at)->format('d M Y H:i') ?? '—' }}</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Approval Steps</x-slot>
                <div class="space-y-3">
                    @foreach ($record->steps as $step)
                        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">Step {{ $step->step_order }} · {{ $step->node_key }}</span>
                                <x-filament::badge :color="match($step->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Escalated' => 'warning', default => 'gray' }">
                                    {{ $step->status }}
                                </x-filament::badge>
                            </div>
                            <div class="mt-2 space-y-1">
                                @foreach ($step->actions as $action)
                                    <div class="text-xs text-gray-500 flex items-center justify-between">
                                        <span>{{ $action->approver?->name ?? 'Unassigned' }} — {{ $action->resolved_via }}</span>
                                        <span>{{ $action->action ?? 'Pending' }}</span>
                                    </div>
                                    @if ($action->comments)
                                        <p class="text-xs italic text-gray-400 pl-2">"{{ $action->comments }}"</p>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if ($record->steps->isEmpty())
                        <p class="text-sm text-gray-400">No steps yet.</p>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Comments</x-slot>
                <div class="space-y-2 mb-3">
                    @forelse ($record->comments as $comment)
                        <div class="text-sm">
                            <span class="font-medium">{{ $comment->user?->name ?? 'System' }}</span>
                            <span class="text-gray-400 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
                            <p class="text-gray-600 dark:text-gray-400">{{ $comment->body }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No comments yet.</p>
                    @endforelse
                </div>
                <form wire:submit.prevent="addComment" class="flex gap-2">
                    <input type="text" wire:model="commentBody" placeholder="Add a comment…" class="fi-input flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                    <x-filament::button type="submit" size="sm">Post</x-filament::button>
                </form>
            </x-filament::section>
        </div>

        <div class="space-y-4">
            @if ($myAction)
                <x-filament::section>
                    <x-slot name="heading">Your Action</x-slot>
                    <textarea wire:model="actionComment" rows="2" placeholder="Optional comment…" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm mb-3"></textarea>

                    @if (! $this->canApprove())
                        <p class="text-xs text-gray-400 mb-2">Your project assignment doesn't permit approving this request.</p>
                    @endif

                    @if ($this->requiresSignature())
                        <div class="mb-3">
                            <label class="block text-xs text-gray-500 mb-1">Type your full name to sign</label>
                            <input type="text" wire:model="signatureName" placeholder="Full name" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                        </div>
                        @if ($this->canApprove())
                            <x-filament::button wire:click="signAndApprove" color="success" size="sm" class="w-full mb-2">
                                Sign &amp; Approve
                            </x-filament::button>
                        @endif
                        <div class="grid grid-cols-2 gap-2">
                            <x-filament::button wire:click="reject" color="danger" size="sm">Reject</x-filament::button>
                            <x-filament::button wire:click="returnForRevision" color="warning" size="sm">Return</x-filament::button>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-2">
                            @if ($this->canApprove())
                                <x-filament::button wire:click="approve" color="success" size="sm">Approve</x-filament::button>
                            @endif
                            <x-filament::button wire:click="reject" color="danger" size="sm">Reject</x-filament::button>
                            <x-filament::button wire:click="returnForRevision" color="warning" size="sm">Return</x-filament::button>
                            <x-filament::button wire:click="requestInformation" color="gray" size="sm">Request Info</x-filament::button>
                        </div>
                    @endif
                </x-filament::section>
            @endif

            @if (! $record->isTerminal())
                <x-filament::section>
                    <x-slot name="heading">Manage</x-slot>
                    <x-filament::button wire:click="cancelRequest" wire:confirm="Cancel this approval request?" color="danger" size="sm" outlined class="w-full">
                        Cancel Request
                    </x-filament::button>
                </x-filament::section>
            @endif

            <x-filament::section>
                <x-slot name="heading">Timeline</x-slot>
                <div class="space-y-3">
                    @foreach ($timeline as $entry)
                        <div class="text-xs border-l-2 border-primary-300 pl-2">
                            <div class="font-medium text-gray-800 dark:text-gray-200">{{ ucfirst($entry->action) }}</div>
                            <div class="text-gray-400">{{ $entry->user?->name ?? 'System' }} · {{ $entry->created_at->format('d M Y H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
