<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Task;
use App\Models\Apartment;
use App\Models\Tenant;
use Livewire\Attributes\Rule;

new class extends Component
{
    use Toast;

    public Apartment $apartment;
    public ?Task $selectedTask = null;
    public bool $showTaskModal = false;
    public bool $showCreateTaskModal = false;

    // Task creation fields
    #[Rule('required|min:3')]
    public string $taskTitle = '';

    #[Rule('sometimes')]
    public ?string $taskDescription = null;

    #[Rule('required')]
    public string $taskType = 'other';

    #[Rule('required')]
    public string $taskStatus = 'todo';

    #[Rule('required')]
    public string $taskPriority = 'medium';

    #[Rule('sometimes')]
    public ?string $taskDueDate = null;

    #[Rule('sometimes')]
    public ?int $taskTenantId = null;

    // Comment field
    #[Rule('required|min:1')]
    public string $commentText = '';

    public function mount(Apartment $apartment): void
    {
        $this->apartment = $apartment;
    }

    public function getTasksProperty(): array
    {
        if (!$this->apartment || !$this->apartment->exists) {
            return $this->getEmptyTasksArray();
        }

        $groupedTasks = $this->apartment->tasks()
            ->with(['tenant', 'comments.user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('status');
        
        // Ensure all status keys exist, even if empty - convert to array for Livewire
        $allStatuses = ['todo', 'in_progress', 'done', 'cancelled'];
        $tasks = [];
        foreach ($allStatuses as $status) {
            $collection = $groupedTasks->get($status, collect());
            // Convert collection to array for Livewire serialization
            $tasks[$status] = $collection->values()->all();
        }
        
        return $tasks;
    }

    protected function getEmptyTasksArray(): array
    {
        return [
            'todo' => [],
            'in_progress' => [],
            'done' => [],
            'cancelled' => [],
        ];
    }

    public function with(): array
    {
        $tasks = $this->tasks;
        
        // Ensure tasks is always an array (not a Collection)
        if ($tasks instanceof \Illuminate\Support\Collection) {
            $tasks = $tasks->toArray();
        }
        
        // Double-check all keys exist
        $allStatuses = ['todo', 'in_progress', 'done', 'cancelled'];
        foreach ($allStatuses as $status) {
            if (!isset($tasks[$status]) || !is_array($tasks[$status])) {
                $tasks[$status] = [];
            }
        }
        
        return [
            'tasks' => $tasks,
            'tenants' => $this->apartment->tenants()
                ->where('status', 'active')
                ->get()
                ->map(fn($tenant) => ['id' => $tenant->id, 'name' => $tenant->name])
                ->toArray(),
        ];
    }

    public function openTaskModal(Task $task): void
    {
        $this->selectedTask = $task->load(['tenant', 'comments.user']);
        $this->showTaskModal = true;
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->selectedTask = null;
        $this->commentText = '';
    }

    public function openCreateTaskModal(): void
    {
        $this->reset(['taskTitle', 'taskDescription', 'taskType', 'taskStatus', 'taskPriority', 'taskDueDate', 'taskTenantId']);
        $this->showCreateTaskModal = true;
    }

    public function closeCreateTaskModal(): void
    {
        $this->showCreateTaskModal = false;
        $this->reset(['taskTitle', 'taskDescription', 'taskType', 'taskStatus', 'taskPriority', 'taskDueDate', 'taskTenantId']);
    }

    public function createTask(): void
    {
        $data = $this->validate([
            'taskTitle' => 'required|min:3',
            'taskDescription' => 'sometimes',
            'taskType' => 'required',
            'taskStatus' => 'required',
            'taskPriority' => 'required',
            'taskDueDate' => 'sometimes|date',
            'taskTenantId' => 'sometimes|exists:tenants,id',
        ]);

        Task::create([
            'apartment_id' => $this->apartment->id,
            'owner_id' => auth()->id(),
            'tenant_id' => $data['taskTenantId'] ?? null,
            'title' => $data['taskTitle'],
            'description' => $data['taskDescription'] ?? null,
            'type' => $data['taskType'],
            'status' => $data['taskStatus'],
            'priority' => $data['taskPriority'],
            'due_date' => $data['taskDueDate'] ?? null,
        ]);

        $this->success('Task created successfully.');
        $this->closeCreateTaskModal();
        $this->dispatch('task-created');
    }

    public function updateTaskStatus(int $taskId, string $newStatus): void
    {
        $task = Task::findOrFail($taskId);
        
        // Ensure the task belongs to this apartment
        if ($task->apartment_id !== $this->apartment->id) {
            $this->error('Unauthorized access to task.');
            return;
        }

        $task->status = $newStatus;
        
        // Set completed_at if moving to done
        if ($newStatus === 'done' && !$task->completed_at) {
            $task->completed_at = now();
        } elseif ($newStatus !== 'done') {
            $task->completed_at = null;
        }
        
        $task->save();

        $this->dispatch('task-status-updated');
    }

    public function addComment(): void
    {
        if (!$this->selectedTask) {
            return;
        }

        $this->validate(['commentText' => 'required|min:1']);

        $this->selectedTask->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $this->commentText,
        ]);

        $this->commentText = '';
        $this->selectedTask->refresh();
        $this->selectedTask->load(['comments.user']);
        $this->success('Comment added successfully.');
    }

    public function getTaskTypeLabel(string $type): string
    {
        return match($type) {
            'payment_followup' => 'Payment Follow-up',
            'electricity_bill' => 'Electricity Bill',
            'water_bill' => 'Water Bill',
            'inquiry' => 'Inquiry',
            'maintenance' => 'Maintenance',
            'contract_renewal' => 'Contract Renewal',
            'move_out' => 'Move-out',
            default => 'Other',
        };
    }

    public function getPriorityColor(string $priority): string
    {
        return match($priority) {
            'high' => 'badge-error',
            'medium' => 'badge-warning',
            'low' => 'badge-info',
            default => 'badge-ghost',
        };
    }

    public function isOverdue(?string $dueDate): bool
    {
        if (!$dueDate) {
            return false;
        }
        return \Carbon\Carbon::parse($dueDate)->isPast() && !\Carbon\Carbon::parse($dueDate)->isToday();
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Task & Requests Board</h2>
        <x-button label="Add Task" icon="o-plus" wire:click="openCreateTaskModal" class="btn-primary" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" 
         x-data="{
             init() {
                 const columns = ['todo', 'in_progress', 'done', 'cancelled'];
                 columns.forEach(status => {
                     const el = document.getElementById('kanban-' + status);
                     if (el) {
                         Sortable.create(el, {
                             group: 'kanban',
                             animation: 150,
                             onEnd: (evt) => {
                                 const taskId = parseInt(evt.item.dataset.taskId);
                                 const newStatus = evt.to.id.replace('kanban-', '');
                                 @this.updateTaskStatus(taskId, newStatus);
                             }
                         });
                     }
                 });
             }
         }">
        
        @foreach(['todo' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done', 'cancelled' => 'Cancelled'] as $status => $label)
            @php
                // Ensure $tasks is an array, not a Collection
                $tasksArray = is_array($tasks) ? $tasks : (is_object($tasks) && method_exists($tasks, 'toArray') ? $tasks->toArray() : []);
                $statusTasks = $tasksArray[$status] ?? [];
                $taskCount = is_countable($statusTasks) ? count($statusTasks) : 0;
            @endphp
            <div class="bg-base-100 rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-lg">{{ $label }}</h3>
                    <span class="badge badge-ghost">{{ $taskCount }}</span>
                </div>
                
                <div id="kanban-{{ $status }}" class="min-h-[200px] space-y-3">
                    @if($taskCount > 0 && is_iterable($statusTasks))
                        @foreach($statusTasks as $task)
                            <div 
                                data-task-id="{{ $task->id }}"
                                wire:click="openTaskModal({{ $task->id }})"
                                class="bg-base-200 p-3 rounded-lg cursor-pointer hover:shadow-md transition-shadow border-l-4 
                                       {{ $this->isOverdue($task->due_date?->format('Y-m-d')) ? 'border-error' : 'border-primary' }}"
                                x-data="{ overdue: {{ $this->isOverdue($task->due_date?->format('Y-m-d')) ? 'true' : 'false' }} }">
                                
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-semibold text-sm flex-1">{{ $task->title }}</h4>
                                    <span class="badge {{ $this->getPriorityColor($task->priority) }} badge-xs ml-2">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </div>
                                
                                @if($task->description)
                                    <p class="text-xs text-base-content/70 mb-2 line-clamp-2">{{ Str::limit($task->description, 60) }}</p>
                                @endif
                                
                                <div class="flex items-center justify-between text-xs">
                                    <span class="badge badge-ghost badge-sm">
                                        {{ $this->getTaskTypeLabel($task->type) }}
                                    </span>
                                    @if($task->due_date)
                                        <span class="text-base-content/70" :class="overdue ? 'text-error font-semibold' : ''">
                                            <x-icon name="o-calendar" class="w-3 h-3 inline" />
                                            {{ $task->due_date->format('M d') }}
                                        </span>
                                    @endif
                                </div>
                                
                                @if($task->tenant)
                                    <div class="mt-2 text-xs text-base-content/60">
                                        <x-icon name="o-user" class="w-3 h-3 inline" />
                                        {{ $task->tenant->name }}
                                    </div>
                                @endif
                                
                                @if($task->comments->count() > 0)
                                    <div class="mt-2 text-xs text-base-content/60">
                                        <x-icon name="o-chat-bubble-left" class="w-3 h-3 inline" />
                                        {{ $task->comments->count() }} comment(s)
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Task Detail Modal -->
    <x-modal wire:model="showTaskModal" title="Task Details" class="backdrop-blur">
        @if($selectedTask)
            <div class="space-y-4">
                <div>
                    <h3 class="text-xl font-bold mb-2">{{ $selectedTask->title }}</h3>
                    <div class="flex gap-2 mb-4">
                        <span class="badge {{ $this->getPriorityColor($selectedTask->priority) }}">
                            {{ ucfirst($selectedTask->priority) }} Priority
                        </span>
                        <span class="badge badge-ghost">
                            {{ $this->getTaskTypeLabel($selectedTask->type) }}
                        </span>
                        @if($selectedTask->due_date)
                            <span class="badge {{ $this->isOverdue($selectedTask->due_date->format('Y-m-d')) ? 'badge-error' : 'badge-info' }}">
                                Due: {{ $selectedTask->due_date->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if($selectedTask->description)
                    <div>
                        <h4 class="font-semibold mb-2">Description</h4>
                        <p class="text-base-content/70 whitespace-pre-line">{{ $selectedTask->description }}</p>
                    </div>
                @endif

                @if($selectedTask->tenant)
                    <div>
                        <h4 class="font-semibold mb-2">Tenant</h4>
                        <p class="text-base-content/70">{{ $selectedTask->tenant->name }}</p>
                    </div>
                @endif

                <div>
                    <h4 class="font-semibold mb-2">Comments</h4>
                    <div class="space-y-3 max-h-60 overflow-y-auto mb-4">
                        @if($selectedTask->comments->count() > 0)
                            @foreach($selectedTask->comments as $comment)
                                <div class="bg-base-200 p-3 rounded-lg">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-semibold text-sm">{{ $comment->user->name }}</span>
                                        <span class="text-xs text-base-content/60">{{ $comment->created_at->format('M d, Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-base-content/70">{{ $comment->comment }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-base-content/60 text-sm">No comments yet.</p>
                        @endif
                    </div>

                    <x-form wire:submit="addComment">
                        <x-textarea 
                            label="Add Comment" 
                            wire:model="commentText" 
                            rows="3"
                            placeholder="Type your comment here..." />
                        <x-slot:actions>
                            <x-button label="Add Comment" type="submit" icon="o-paper-airplane" class="btn-primary btn-sm" />
                        </x-slot:actions>
                    </x-form>
                </div>
            </div>
        @endif
    </x-modal>

    <!-- Create Task Modal -->
    <x-modal wire:model="showCreateTaskModal" title="Create New Task" class="backdrop-blur">
        <x-form wire:submit="createTask">
            <x-input label="Title" wire:model="taskTitle" />
            <x-textarea label="Description" wire:model="taskDescription" rows="3" />
            
            <x-select 
                label="Type" 
                wire:model="taskType" 
                :options="[
                    ['id' => 'payment_followup', 'name' => 'Payment Follow-up'],
                    ['id' => 'electricity_bill', 'name' => 'Electricity Bill'],
                    ['id' => 'water_bill', 'name' => 'Water Bill'],
                    ['id' => 'inquiry', 'name' => 'Inquiry'],
                    ['id' => 'maintenance', 'name' => 'Maintenance'],
                    ['id' => 'contract_renewal', 'name' => 'Contract Renewal'],
                    ['id' => 'move_out', 'name' => 'Move-out'],
                    ['id' => 'other', 'name' => 'Other'],
                ]" />
            
            <x-select 
                label="Status" 
                wire:model="taskStatus" 
                :options="[
                    ['id' => 'todo', 'name' => 'To Do'],
                    ['id' => 'in_progress', 'name' => 'In Progress'],
                    ['id' => 'done', 'name' => 'Done'],
                    ['id' => 'cancelled', 'name' => 'Cancelled'],
                ]" />
            
            <x-select 
                label="Priority" 
                wire:model="taskPriority" 
                :options="[
                    ['id' => 'low', 'name' => 'Low'],
                    ['id' => 'medium', 'name' => 'Medium'],
                    ['id' => 'high', 'name' => 'High'],
                ]" />
            
            <x-input label="Due Date" wire:model="taskDueDate" type="date" />
            
            @if(!empty($tenants) && count($tenants) > 0)
                <x-select 
                    label="Tenant (Optional)" 
                    wire:model="taskTenantId" 
                    :options="$tenants" 
                    placeholder="Select tenant..." />
            @endif

            <x-slot:actions>
                <x-button label="Cancel" wire:click="closeCreateTaskModal" />
                <x-button label="Create Task" type="submit" icon="o-plus" class="btn-primary" spinner="createTask" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
