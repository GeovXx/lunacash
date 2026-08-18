<?php

namespace App\Livewire;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public $notifications = [];

    public $unreadCount = 0;

    public $hasEphemeral = false;

    public $isOpen = false;

    protected $listeners = ['notificationCreated' => 'loadNotifications'];

    public function mount(NotificationService $service)
    {
        $this->loadNotifications($service);
    }

    public function loadNotifications(NotificationService $service)
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->unreadCount = $service->getUnreadPersistentCount($user);
        $this->hasEphemeral = $service->hasEphemeralAlerts($user);

        // We only load the actual notifications array if the dropdown is open to save memory/perf on every page load
        if ($this->isOpen) {
            $this->notifications = $service->getNotifications($user, 15);
        }
    }

    public function toggleDropdown(NotificationService $service)
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen) {
            $this->loadNotifications($service);
        }
    }

    public function markAsRead(NotificationService $service, $id)
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if ($service->markAsRead($id, $user)) {
            $this->loadNotifications($service);
        }
    }

    public function markAllAsRead(NotificationService $service)
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->loadNotifications($service);
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
