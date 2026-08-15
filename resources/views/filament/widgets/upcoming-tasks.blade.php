<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Upcoming Tasks</x-slot>

        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getTasks() as $task)
                        <tr>
                            <td><span class="badge gray">{{ $task['type'] }}</span></td>
                            <td>{{ $task['description'] }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($task['date'])->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-row">No upcoming tasks.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
