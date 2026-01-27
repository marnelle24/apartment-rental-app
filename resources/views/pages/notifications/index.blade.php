    <?php

    use App\Models\Notification;
    use Livewire\Component;
    use Mary\Traits\Toast;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Pagination\LengthAwarePaginator;
    use Livewire\WithPagination;
    use App\Traits\AuthorizesRole;
    use Illuminate\Support\Str;

    new class extends Component {
        use Toast;
        use WithPagination;
        use AuthorizesRole;

        public string $search = '';
        public string $type = '';
        public string $status = ''; // 'all', 'read', 'unread'
        public bool $drawer = false;
        public bool $showModal = false;
        public ?Notification $selectedNotification = null;

        public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

        // Check owner access on mount
        public function mount(): void
        {
            $this->authorizeRole('owner');
        }

        // Clear filters
        public function clear(): void
        {
            $this->reset(['search', 'type', 'status']);
            $this->resetPage(); 
            $this->success('Filters cleared.', position: 'toast-bottom');
        }

        // Mark notification as read
        public function markAsRead(Notification $notification): void
        {
            if ($notification->user_id !== auth()->id()) {
                $this->error('Unauthorized access.', position: 'toast-bottom');
                return;
            }

            if ($notification->markAsRead()) {
                $this->success('Notification marked as read.', position: 'toast-bottom');
            }
        }

        // Mark notification as unread
        public function markAsUnread(Notification $notification): void
        {
            if ($notification->user_id !== auth()->id()) {
                $this->error('Unauthorized access.', position: 'toast-bottom');
                return;
            }

            if ($notification->markAsUnread()) {
                $this->success('Notification marked as unread.', position: 'toast-bottom');
            }
        }

        // Mark all as read
        public function markAllAsRead(): void
        {
            $count = Notification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            if ($count > 0) {
                $this->success("Marked {$count} notification(s) as read.", position: 'toast-bottom');
            } else {
                $this->info('No unread notifications to mark.', position: 'toast-bottom');
            }
        }

        // Delete notification
        public function delete(Notification $notification): void
        {
            if ($notification->user_id !== auth()->id()) {
                $this->error('Unauthorized access.', position: 'toast-bottom');
                return;
            }

            $notification->delete();
            $this->warning("Notification deleted", 'Good bye!', position: 'toast-bottom');
        }

        // Open notification modal
        public function openNotificationModal(Notification $notification): void
        {
            if ($notification->user_id !== auth()->id()) {
                $this->error('Unauthorized access.', position: 'toast-bottom');
                return;
            }

            $this->selectedNotification = $notification;
            $this->showModal = true;

            // Mark as read if unread
            if ($notification->isUnread()) {
                $notification->markAsRead();
            }
        }

        // Close notification modal
        public function closeNotificationModal(): void
        {
            $this->showModal = false;
            $this->selectedNotification = null;
        }

        // Table headers
        public function headers(): array
        {
            return [
                ['key' => 'read_status', 'label' => '', 'class' => 'w-1'],
                ['key' => 'type', 'label' => 'Type', 'class' => 'w-32'],
                ['key' => 'title', 'label' => 'Title', 'class' => 'w-64'],
                ['key' => 'message', 'label' => 'Message', 'class' => ''],
                ['key' => 'created_at', 'label' => 'Date', 'class' => 'w-40'],
                ['key' => 'actions', 'label' => '', 'class' => 'w-20'],
            ];
        }

        public function notifications(): LengthAwarePaginator
        {
            return Notification::query()
                ->where('user_id', auth()->id())
                ->when($this->search, fn(Builder $q) => $q->where('title', 'like', "%$this->search%")
                    ->orWhere('message', 'like', "%$this->search%"))
                ->when($this->type, fn(Builder $q) => $q->where('type', $this->type))
                ->when($this->status === 'read', fn(Builder $q) => $q->whereNotNull('read_at'))
                ->when($this->status === 'unread', fn(Builder $q) => $q->whereNull('read_at'))
                ->orderBy(...array_values($this->sortBy))
                ->paginate(15);
        }

        public function getUnreadCountProperty(): int
        {
            return Notification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->count();
        }

        public function with(): array
        {
            return [
                'notifications' => $this->notifications(),
                'headers' => $this->headers(),
                'unreadCount' => $this->unreadCount,
                'types' => [
                    ['id' => 'overdue_payment', 'name' => 'Overdue Payment'],
                    ['id' => 'lease_expiration', 'name' => 'Lease Expiration'],
                ],
                'statuses' => [
                    ['id' => 'all', 'name' => 'All'],
                    ['id' => 'unread', 'name' => 'Unread'],
                    ['id' => 'read', 'name' => 'Read'],
                ],
            ];
        }

        // Reset pagination when any component property changes
        public function updated($property): void
        {
            $this->resetPage();
        }
    }; ?>

    <div>
        <!-- HEADER -->
        <x-header title="Notifications" separator progress-indicator>
            <x-slot:actions>
                @if($unreadCount > 0)
                    <x-button 
                        label="Mark All as Read" 
                        icon="o-check-circle" 
                        wire:click="markAllAsRead" 
                        spinner="markAllAsRead"
                        class="btn-primary"
                    />
                @endif
            </x-slot:actions>
        </x-header>

        <!-- FILTERS -->
        <x-card class="mb-4">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                <div class="col-span-3">
                    <x-input 
                        label="Search" 
                        wire:model.live.debounce.500ms="search" 
                        icon="o-magnifying-glass" 
                        placeholder="Search notifications..."
                        class="w-full"
                    />
                </div>
                <div class="col-span-1">
                    <x-select 
                        label="Status" 
                        wire:model.live="status" 
                        :options="$statuses" 
                        option-value="id" 
                        option-label="name"
                        placeholder="All"
                        class="w-full"
                    />
                </div>
                <div class="col-span-1">
                    <x-select 
                        label="Type" 
                        wire:model.live="type" 
                        :options="$types" 
                        option-value="id" 
                        option-label="name"
                        placeholder="All Types"
                        class="w-full"
                    />
                </div>
                <div class="col-span-1 flex justify-end">
                    <button 
                        type="button"
                        title="reset filters"
                        wire:click="clear" 
                        class="border border-gray-300 bg-gray-100 p-2 flex items-center justify-center gap-2 cursor-pointer hover:bg-gray-200 transition-colors w-full"
                    >
                        <x-icon name="o-x-mark" class="w-4 h-4" />
                        Reset
                    </button>
                </div>
            </div>
        </x-card>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-card class="bg-base-100 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-base-content/60">Total Notifications</div>
                        <div class="text-3xl font-bold">{{ $notifications->total() }}</div>
                    </div>
                    <x-icon name="o-bell" class="w-10 h-10 text-primary opacity-50" />
                </div>
            </x-card>

            <x-card class="bg-base-100 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-base-content/60">Unread</div>
                        <div class="text-3xl font-bold text-warning">{{ $unreadCount }}</div>
                    </div>
                    <x-icon name="o-envelope" class="w-10 h-10 text-warning opacity-50" />
                </div>
            </x-card>

            <x-card class="bg-base-100 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-base-content/60">Read</div>
                        <div class="text-3xl font-bold text-success">{{ $notifications->total() - $unreadCount }}</div>
                    </div>
                    <x-icon name="o-check-circle" class="w-10 h-10 text-success opacity-50" />
                </div>
            </x-card>
        </div>

        <!-- NOTIFICATIONS TABLE -->
        <x-card class="bg-base-100 shadow">
            <x-table :headers="$headers" :rows="$notifications" :sort-by="$sortBy" with-pagination>
                @scope('cell_read_status', $notification)
                    @if($notification->read_at)
                        <x-icon name="o-check-circle" class="w-5 h-5 text-success" />
                    @else
                        <x-icon name="o-envelope" class="w-5 h-5 text-warning" />
                    @endif
                @endscope

                @scope('cell_type', $notification)
                    @php
                        $typeColors = [
                            'payment_overdue' => 'badge-error',
                            'lease_expiring' => 'badge-warning',
                            'payment_received' => 'badge-success',
                            'maintenance_request' => 'badge-info',
                        ];
                        $color = $typeColors[$notification->type] ?? 'badge-ghost';
                        $typeLabels = [
                            'payment_overdue' => 'Overdue',
                            'lease_expiring' => 'Lease Expiring',
                            'payment_received' => 'Payment Received',
                            'maintenance_request' => 'Maintenance Request',
                        ];
                        $typeLabel = $typeLabels[$notification->type] ?? str_replace('_', ' ', ucwords($notification->type, '_'));
                    @endphp
                    <span class="badge {{ $color }} badge-sm whitespace-nowrap text-white">
                        {{ $typeLabel }}
                    </span>
                @endscope

                @scope('cell_title', $notification)
                    <div 
                        class="{{ $notification->read_at ? 'text-base-content/70' : 'font-bold text-base-content' }} cursor-pointer hover:text-primary transition-colors"
                        wire:click="openNotificationModal({{ $notification->id }})"
                    >
                        {{ $notification->title }}
                    </div>
                @endscope

                @scope('cell_message', $notification)
                    <div 
                        class="text-sm {{ $notification->read_at ? 'text-base-content/60' : 'text-base-content' }} cursor-pointer hover:text-primary transition-colors"
                        wire:click="openNotificationModal({{ $notification->id }})"
                    >
                        {{ Str::limit($notification->message, 100) }}
                    </div>
                @endscope

                @scope('cell_created_at', $notification)
                    <div class="text-sm text-base-content/60">
                        {{ $notification->created_at->format('M d, Y') }}
                        <div class="text-xs text-base-content/50">
                            {{ $notification->created_at->format('h:i A') }}
                        </div>
                    </div>
                @endscope

                @scope('actions', $notification)
                    <div class="flex gap-2">
                        @if($notification->read_at)
                            <x-button 
                                icon="o-envelope" 
                                wire:click="markAsUnread({{ $notification->id }})" 
                                wire:confirm="Mark as unread?"
                                spinner
                                class="btn-ghost btn-sm"
                                tooltip="Mark as unread"
                            />
                        @else
                            <x-button 
                                icon="o-check-circle" 
                                wire:click="markAsRead({{ $notification->id }})" 
                                spinner
                                class="btn-ghost btn-sm text-success"
                                tooltip="Mark as read"
                            />
                        @endif
                        <x-button 
                            icon="o-trash" 
                            wire:click="delete({{ $notification->id }})" 
                            wire:confirm="Are you sure? This will delete the notification."
                            spinner
                            class="btn-ghost btn-sm text-error"
                            tooltip="Delete"
                        />
                    </div>
                @endscope
            </x-table>
        </x-card>

        <!-- EMPTY STATE -->
        @if($notifications->count() === 0)
            <x-card class="bg-base-100 shadow">
                <div class="text-center py-12">
                    <x-icon name="o-bell-slash" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <p class="text-lg font-semibold text-base-content/70 mb-2">No notifications found</p>
                    <p class="text-sm text-base-content/50">
                        @if($search || $type || $status)
                            Try adjusting your filters to see more results.
                        @else
                            You're all caught up! No notifications at the moment.
                        @endif
                    </p>
                </div>
            </x-card>
        @endif

        <!-- NOTIFICATION DETAIL MODAL -->
        <x-modal wire:model="showModal" title="Notification Details" class="backdrop-blur" separator>
            @if($selectedNotification)
                <div class="space-y-6">
                    <!-- Notification Status Badge -->
                    <div class="flex items-center gap-3">
                        @if($selectedNotification->read_at)
                            <span class="badge badge-success gap-2">
                                <x-icon name="o-check-circle" class="w-4 h-4" />
                                Read
                            </span>
                        @else
                            <span class="badge badge-warning gap-2">
                                <x-icon name="o-envelope" class="w-4 h-4" />
                                Unread
                            </span>
                        @endif
                        @php
                            $typeColors = [
                                'payment_overdue' => 'badge-error',
                                'lease_expiring' => 'badge-warning',
                                'payment_received' => 'badge-success',
                                'maintenance_request' => 'badge-info',
                            ];
                            $color = $typeColors[$selectedNotification->type] ?? 'badge-ghost';
                            $typeLabels = [
                                'payment_overdue' => 'Overdue Payment',
                                'lease_expiring' => 'Lease Expiration',
                                'payment_received' => 'Payment Received',
                                'maintenance_request' => 'Maintenance Request',
                            ];
                            $typeLabel = $typeLabels[$selectedNotification->type] ?? str_replace('_', ' ', ucwords($selectedNotification->type, '_'));
                        @endphp
                        <span class="badge {{ $color }} badge-sm text-white">
                            {{ $typeLabel }}
                        </span>
                    </div>

                    <!-- Title -->
                    <div>
                        <h3 class="text-2xl font-bold text-base-content mb-2">
                            {{ $selectedNotification->title }}
                        </h3>
                    </div>

                    <!-- Message -->
                    <div>
                        <h4 class="font-semibold mb-3 text-base-content/80">Message</h4>
                        <div class="bg-base-200 rounded-lg p-4">
                            <p class="text-base-content/90 whitespace-pre-line leading-relaxed">
                                {{ $selectedNotification->message }}
                            </p>
                        </div>
                    </div>

                    <!-- Notification Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-base-300">
                        <div>
                            <h4 class="font-semibold mb-2 text-base-content/80">Created At</h4>
                            <div class="flex items-center gap-2 text-base-content/70">
                                <x-icon name="o-calendar" class="w-5 h-5" />
                                <span>{{ $selectedNotification->created_at->format('F d, Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-base-content/60 text-sm mt-1 ml-7">
                                <x-icon name="o-clock" class="w-4 h-4" />
                                <span>{{ $selectedNotification->created_at->format('h:i A') }}</span>
                            </div>
                        </div>

                        @if($selectedNotification->read_at)
                            <div>
                                <h4 class="font-semibold mb-2 text-base-content/80">Read At</h4>
                                <div class="flex items-center gap-2 text-base-content/70">
                                    <x-icon name="o-check-circle" class="w-5 h-5 text-success" />
                                    <span>{{ $selectedNotification->read_at->format('F d, Y') }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-base-content/60 text-sm mt-1 ml-7">
                                    <x-icon name="o-clock" class="w-4 h-4" />
                                    <span>{{ $selectedNotification->read_at->format('h:i A') }}</span>
                                </div>
                            </div>
                        @endif

                        <div>
                            <h4 class="font-semibold mb-2 text-base-content/80">Notification ID</h4>
                            <div class="flex items-center gap-2 text-base-content/60 text-sm">
                                <x-icon name="o-hashtag" class="w-4 h-4" />
                                <span>#{{ $selectedNotification->id }}</span>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold mb-2 text-base-content/80">Type</h4>
                            <div class="flex items-center gap-2 text-base-content/70">
                                <x-icon name="o-tag" class="w-5 h-5" />
                                <span class="capitalize">{{ str_replace('_', ' ', $selectedNotification->type) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <x-slot:actions>
                    <x-button 
                        label="Close" 
                        @click="$wire.closeNotificationModal()" 
                        class="btn-primary"
                    />
                    @if($selectedNotification->read_at)
                        <x-button 
                            label="Mark as Unread" 
                            icon="o-envelope"
                            wire:click="markAsUnread({{ $selectedNotification->id }})" 
                            spinner
                            class="btn-outline text-info hover:bg-info hover:text-white"
                        />
                    @else
                        <x-button 
                            label="Mark as Read" 
                            icon="o-check-circle"
                            wire:click="markAsRead({{ $selectedNotification->id }})" 
                            spinner
                            class="btn-outline btn-info text-info hover:bg-info hover:text-white"
                        />
                    @endif
                    <x-button 
                        label="Delete" 
                        icon="o-trash"
                        wire:click="delete({{ $selectedNotification->id }})" 
                        wire:confirm="Are you sure? This will delete the notification."
                        spinner
                        class="btn-outline btn-sang text-error"
                    />
                </x-slot:actions>
            @endif
        </x-modal>
    </div>
