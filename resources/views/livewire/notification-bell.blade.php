<div class="relative" x-data="{ open: @entangle('isOpen') }">
    <!-- Bell Button -->
    <button 
        wire:click="toggleDropdown" 
        class="relative p-2 text-gray-400 hover:text-gray-500 transition-colors focus:outline-none"
        aria-label="Notificações"
    >
        <x-lucide-bell class="w-6 h-6" />
        
        <!-- Persistent Unread Badge -->
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full transform translate-x-1/4 -translate-y-1/4">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
        
        <!-- Ephemeral Indicator (Dot) -->
        @if($hasEphemeral)
            <span class="absolute bottom-0 right-0 inline-flex w-3 h-3 bg-yellow-400 border-2 border-white rounded-full transform translate-x-1/4 translate-y-1/4 dark:border-gray-800" title="Atenção pendente"></span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div 
        x-show="open" 
        @click.away="open = false; @this.set('isOpen', false)"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 sm:right-0 -mr-2 sm:mr-0 z-50 w-[calc(100vw-2rem)] max-w-sm sm:w-80 mt-2 origin-top-right bg-white border border-gray-100 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:border-gray-700"
        style="display: none;"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notificações</h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                    Marcar todas como lidas
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            <div wire:loading wire:target="toggleDropdown, loadNotifications" class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                Carregando...
            </div>
            
            <div wire:loading.remove wire:target="toggleDropdown, loadNotifications">
                @forelse($notifications as $notification)
                    @php
                        $isEphemeral = $notification['is_ephemeral'];
                        $isRead = $notification['is_read'];
                        $type = $notification['type'];
                        
                        $bgColor = $isRead ? 'bg-white dark:bg-gray-800' : 'bg-indigo-50 dark:bg-gray-700/50';
                        if ($isEphemeral) {
                            $bgColor = 'bg-yellow-50 dark:bg-yellow-900/20'; // Distinct color for ephemeral
                        }

                        $iconClass = 'text-gray-400';
                        $icon = 'bell';
                        
                        // Semantic Icons & Colors
                        if (in_array($type, ['overdue', 'budget_exceeded'])) {
                            $iconClass = 'text-red-500';
                            $icon = 'alert-triangle';
                        } elseif (in_array($type, ['due_soon', 'invoice_due_soon', 'budget_warning', 'goal_due_soon'])) {
                            $iconClass = 'text-yellow-500';
                            $icon = 'clock';
                        } elseif (in_array($type, ['goal_completed'])) {
                            $iconClass = 'text-green-500';
                            $icon = 'check-circle';
                        }
                    @endphp

                    <div class="relative px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0 {{ $bgColor }}">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($icon === 'alert-triangle')
                                    <x-lucide-alert-triangle class="w-5 h-5 {{ $iconClass }}" />
                                @elseif($icon === 'clock')
                                    <x-lucide-clock class="w-5 h-5 {{ $iconClass }}" />
                                @elseif($icon === 'check-circle')
                                    <x-lucide-check-circle class="w-5 h-5 {{ $iconClass }}" />
                                @else
                                    <x-lucide-bell class="w-5 h-5 {{ $iconClass }}" />
                                @endif
                            </div>
                            <div class="ml-3 w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $notification['message'] }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if($isEphemeral)
                                        Atenção Imediata
                                    @else
                                        {{ \Carbon\Carbon::parse($notification['created_at'])->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            
                            @if(!$isEphemeral && !$isRead)
                                <div class="ml-3 flex-shrink-0 flex items-center">
                                    <button wire:click.stop="markAsRead('{{ $notification['id'] }}')" class="p-1 rounded-full text-indigo-600 hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-gray-600 focus:outline-none" title="Marcar como lida">
                                        <x-lucide-check class="w-4 h-4" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Nenhuma notificação encontrada.
                    </div>
                @endforelse
            </div>
        </div>
        
        <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">Notificações exibidas visualmente.</p>
        </div>
    </div>
</div>
