<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Calendário</h1>
            <p class="text-sm text-foreground-secondary">
                Visão unificada das suas finanças e projeções futuras.
            </p>
        </div>
        
        <div class="flex items-center gap-2">
            <button wire:click="previousMonth" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-foreground-secondary hover:bg-surface-elevated hover:text-foreground">
                <x-lucide-chevron-left class="h-4 w-4" />
            </button>
            <button wire:click="goToToday" class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-surface px-4 text-sm font-medium text-foreground hover:bg-surface-elevated">
                Hoje
            </button>
            <button wire:click="nextMonth" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-foreground-secondary hover:bg-surface-elevated hover:text-foreground">
                <x-lucide-chevron-right class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div class="flex items-center justify-center py-2">
        <h2 class="text-xl font-semibold capitalize text-foreground">{{ $monthName }} {{ $year }}</h2>
    </div>

    {{-- DESKTOP GRID (Hidden on mobile) --}}
    <div class="hidden md:block">
        <div class="rounded-xl border border-border bg-surface shadow-sm overflow-hidden">
            {{-- Weekdays --}}
            <div class="grid grid-cols-7 border-b border-border bg-surface-elevated">
                @foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dayName)
                    <div class="py-3 text-center text-xs font-medium uppercase tracking-wider text-foreground-secondary">
                        {{ $dayName }}
                    </div>
                @endforeach
            </div>
            
            {{-- Days Grid --}}
            <div class="grid grid-cols-7 gap-px bg-border">
                @foreach($days as $day)
                    <div class="min-h-[120px] bg-surface p-2 transition-colors hover:bg-surface-elevated/50 {{ !$day['isCurrentMonth'] ? 'bg-surface/50 text-foreground-secondary/50' : '' }}">
                        <div class="flex justify-between items-start">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm {{ $day['isToday'] ? 'bg-primary text-primary-foreground font-semibold' : 'text-foreground' }}">
                                {{ $day['day'] }}
                            </span>
                        </div>
                        
                        <div class="mt-2 flex flex-col gap-1">
                            @foreach($day['events'] as $event)
                                @php
                                    $isReceivable = $event['direction'] === 'receivable';
                                    $isNeutral = $event['direction'] === 'neutral';
                                    
                                    if ($isNeutral) {
                                        $colorClass = 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20';
                                    } elseif ($isReceivable) {
                                        $colorClass = 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
                                    } else {
                                        $colorClass = 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
                                    }

                                    // Para faturas e metas, não mostramos o R$ por padrao se ja for formatado?
                                    // A view original formata, vamos usar number_format para ficar bom
                                @endphp
                                <div class="flex flex-col rounded-md px-2 py-1 text-xs {{ $colorClass }}" title="{{ $event['title'] }}">
                                    <div class="flex items-center justify-between gap-1 truncate font-medium">
                                        <span class="truncate">{{ $event['title'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold">{{ $isReceivable && !$isNeutral ? '+' : ($isNeutral ? '' : '-') }} R$ {{ number_format($event['amount'], 2, ',', '.') }}</span>
                                        @if($event['status'] === 'projected')
                                            <x-lucide-calendar-clock class="h-3 w-3 opacity-70" />
                                        @elseif($event['status'] === 'posted' || $event['status'] === 'paid')
                                            <x-lucide-check-circle-2 class="h-3 w-3 opacity-70" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- MOBILE LIST (Hidden on desktop) --}}
    <div class="md:hidden space-y-4">
        @php
            $hasEvents = false;
        @endphp
        
        @foreach($days as $day)
            @if($day['isCurrentMonth'] && count($day['events']) > 0)
                @php $hasEvents = true; @endphp
                <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
                    <div class="mb-3 flex items-center gap-3 border-b border-border pb-3">
                        <div class="flex h-10 w-10 flex-col items-center justify-center rounded-md {{ $day['isToday'] ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated text-foreground' }}">
                            <span class="text-xs font-medium uppercase leading-none">{{ \Carbon\Carbon::parse($day['date'])->translatedFormat('D') }}</span>
                            <span class="text-lg font-bold leading-none mt-0.5">{{ $day['day'] }}</span>
                        </div>
                        <span class="text-sm font-medium text-foreground-secondary">
                            {{ count($day['events']) }} {{ count($day['events']) === 1 ? 'evento' : 'eventos' }}
                        </span>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        @foreach($day['events'] as $event)
                            @php
                                $isReceivable = $event['direction'] === 'receivable';
                                $isNeutral = $event['direction'] === 'neutral';
                            @endphp
                            <div class="flex items-center justify-between rounded-lg border border-border bg-surface-elevated p-3">
                                <div class="flex items-center gap-3 truncate">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $isNeutral ? 'bg-blue-500/10 text-blue-500' : ($isReceivable ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500') }}">
                                        @if($event['source_type'] === 'transaction')
                                            <x-lucide-list class="h-4 w-4" />
                                        @elseif($event['source_type'] === 'obligation')
                                            <x-lucide-receipt class="h-4 w-4" />
                                        @elseif($event['source_type'] === 'invoice')
                                            <x-lucide-credit-card class="h-4 w-4" />
                                        @elseif($event['source_type'] === 'goal')
                                            <x-lucide-target class="h-4 w-4" />
                                        @else
                                            <x-lucide-repeat class="h-4 w-4" />
                                        @endif
                                    </div>
                                    <div class="flex flex-col truncate">
                                        <span class="truncate text-sm font-medium text-foreground">{{ $event['title'] }}</span>
                                        <span class="text-xs text-foreground-secondary">{{ ucfirst($event['status']) }}</span>
                                    </div>
                                </div>
                                <span class="shrink-0 text-sm font-bold {{ $isNeutral ? 'text-blue-600 dark:text-blue-400' : ($isReceivable ? 'text-emerald-600 dark:text-emerald-400' : 'text-foreground') }}">
                                    {{ $isReceivable && !$isNeutral ? '+' : ($isNeutral ? '' : '-') }} R$ {{ number_format($event['amount'], 2, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
        
        @if(!$hasEvents)
            <div class="flex flex-col items-center justify-center rounded-xl border border-border border-dashed py-12 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-elevated">
                    <x-lucide-calendar-x class="h-6 w-6 text-foreground-secondary" />
                </div>
                <h3 class="mt-4 text-sm font-medium text-foreground">Nenhum evento este mês</h3>
                <p class="mt-1 max-w-sm text-sm text-foreground-secondary">Você não possui lançamentos, projeções, obrigações ou metas neste período.</p>
            </div>
        @endif
    </div>
</div>
