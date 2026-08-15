<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Announcement;
use App\Models\Trip;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class NotificationsDropdown extends Component
{
    public $notifications = [];
    public $unreadCount = 0;
    public $currentFilter = 'all';

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $scopeCompany = $user && !$user->hasRole('Super Admin');

        // Fetch Announcements (both read and unread for the "All" tab)
        $allAnnouncements = Announcement::query()
            ->with(['reads' => fn ($q) => $q->where('user_id', $user->id)])
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('audience', 'all')
                    ->orWhere(function ($inner) use ($user) {
                        $inner->where('audience', 'company')
                            ->where('target_company_id', $user->company_id);
                    });
            })
            ->latest()->take(20)->get();

        // Fetch Completed Trips
        $completedTrips = Trip::where('status', 'Completed')
            ->when($scopeCompany, fn ($q) => $q->where('company_id', $user->company_id))
            ->latest()->take(10)->get();

        // Fetch Unpaid Invoices
        $invoices = [];
        try {
            $invoices = Invoice::whereIn('status', ['Unpaid', 'Overdue'])
                ->when($scopeCompany, fn ($q) => $q->where('company_id', $user->company_id))
                ->latest()->take(10)->get();
        } catch (\Exception $e) {
            // Table might not exist yet or other issue
        }

        $merged = [];

        foreach ($allAnnouncements as $ann) {
            $isRead = $ann->reads->isNotEmpty();
            $merged[] = [
                'id' => 'ann_' . $ann->id,
                'real_id' => $ann->id,
                'type' => 'system',
                'unread' => !$isRead,
                'title' => $ann->title,
                'msg' => $ann->message ?? $ann->title, // Use the full message field
                'time' => $ann->created_at->diffForHumans(),
                'model' => 'Announcement'
            ];
        }

        foreach ($invoices as $invoice) {
            $merged[] = [
                'id' => 'invoice_' . $invoice->id,
                'real_id' => $invoice->id,
                'type' => 'billing',
                'unread' => true, 
                'title' => 'Invoice ' . $invoice->status,
                'msg' => "Invoice #{$invoice->invoice_number} for {$invoice->total_amount} is {$invoice->status}.",
                'time' => $invoice->updated_at->diffForHumans(),
                'model' => 'Invoice'
            ];
        }

        foreach ($completedTrips as $trip) {
            $merged[] = [
                'id' => 'trip_' . $trip->id,
                'real_id' => $trip->id,
                'type' => 'client',
                'unread' => true,
                'title' => 'Trip Completed',
                'msg' => "Trip #{$trip->id} from {$trip->origin} to {$trip->destination} completed.",
                'time' => $trip->updated_at->diffForHumans(),
                'model' => 'Trip'
            ];
        }

        // Daily Site Diary alerts, plus FinanceNotification types (delivered via the standard database channel)
        $financeNotificationTypes = [
            'budget_exceeded', 'invoice_overdue', 'retention_due', 'payment_received',
            'forecast_changed', 'cost_variance_high', 'variation_submitted', 'cash_flow_risk',
        ];

        foreach ($user->notifications()->latest()->take(20)->get() as $dbNotification) {
            $notificationType = $dbNotification->data['type'] ?? '';

            if (! str_starts_with($notificationType, 'diary_')
                && ! in_array($notificationType, ['severe_weather', 'high_delay', 'equipment_breakdown', 'material_shortage'])
                && ! in_array($notificationType, $financeNotificationTypes)) {
                continue;
            }

            $merged[] = [
                'id' => 'diary_' . $dbNotification->id,
                'real_id' => $dbNotification->id,
                'type' => 'module',
                'unread' => is_null($dbNotification->read_at),
                'title' => $dbNotification->data['title'] ?? 'Diary Update',
                'msg' => $dbNotification->data['body'] ?? '',
                'time' => $dbNotification->created_at->diffForHumans(),
                'model' => 'DiaryNotification',
            ];
        }

        $this->notifications = $merged;
        $this->updateUnreadCount();
    }

    public function updateUnreadCount()
    {
        $this->unreadCount = collect($this->notifications)->where('unread', true)->count();
    }

    public function markRead($id)
    {
        $notification = collect($this->notifications)->firstWhere('id', $id);
        
        if (!$notification) return;

        if ($notification['model'] === 'Announcement') {
            $ann = Announcement::find($notification['real_id']);
            if ($ann) {
                $ann->reads()->create([
                    'user_id' => Auth::id(),
                    'read_at' => now(),
                ]);
            }
        }

        if ($notification['model'] === 'DiaryNotification') {
            $dbNotification = Auth::user()->notifications()->find($notification['real_id']);
            $dbNotification?->markAsRead();
        }

        $this->notifications = collect($this->notifications)->map(function($n) use ($id) {
            if ($n['id'] === $id) {
                $n['unread'] = false;
            }
            return $n;
        })->toArray();

        $this->updateUnreadCount();
        $this->loadNotifications();
    }

    public function markAllRead()
    {
        foreach ($this->notifications as $n) {
            if ($n['unread']) {
                $this->markRead($n['id']);
            }
        }
    }

    public function render()
    {
        return view('livewire.notifications-dropdown');
    }
}
