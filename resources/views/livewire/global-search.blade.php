<div
    x-data="{ 
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; $wire.clear(); }
    }"
    x-on:keydown.window.ctrl.k.prevent="toggle()"
    x-on:keydown.window.meta.k.prevent="toggle()"
    x-on:keydown.escape.window="close()"
>
    <!-- Modal Backdrop -->
    <div
        x-show="open"
        style="display: none;"
        class="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Modal Content -->
    <div
        x-show="open"
        style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto"
        x-on:click.self="close()"
    >
        <div class="flex min-h-full items-start justify-center p-4 sm:p-6 lg:p-8">
            <div
                class="w-full max-w-2xl overflow-hidden rounded-xl bg-surface shadow-2xl ring-1 ring-border mt-10 sm:mt-20"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-on:click.outside="close()"
            >
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-foreground-muted">
                        <x-lucide-search class="h-5 w-5" />
                    </div>
                    
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="w-full border-0 bg-transparent py-4 pl-12 pr-4 text-foreground placeholder:text-foreground-muted focus:ring-0 sm:text-sm"
                        placeholder="Pesquisar contas, categorias, cartões e lançamentos..."
                        x-init="$watch('open', value => { if (value) setTimeout(() => $el.focus(), 100) })"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    
                    <!-- Loading Indicator -->
                    <div class="absolute right-4 top-4" wire:loading wire:target="search">
                        <x-lucide-loader-2 class="h-5 w-5 animate-spin text-primary" />
                    </div>
                </div>

                @if(strlen($search) >= 2)
                    <div class="max-h-96 overflow-y-auto border-t border-border p-2">
                        @php
                            $hasResults = count($results['accounts']) > 0 || 
                                          count($results['categories']) > 0 || 
                                          count($results['credit_cards']) > 0 || 
                                          count($results['transactions']) > 0;
                        @endphp

                        @if(!$hasResults)
                            <div class="py-14 text-center text-sm sm:px-14">
                                <x-lucide-search-x class="mx-auto h-6 w-6 text-foreground-muted" />
                                <p class="mt-4 font-semibold text-foreground">Nenhum resultado encontrado</p>
                                <p class="mt-2 text-foreground-muted">Não conseguimos encontrar nada para "{{ $search }}". Tente novamente com outros termos.</p>
                            </div>
                        @else
                            <div class="space-y-4 px-2 py-2">
                                <!-- Contas -->
                                @if(count($results['accounts']) > 0)
                                    <div>
                                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-foreground-muted">Contas</h3>
                                        <ul class="mt-2 space-y-1">
                                            @foreach($results['accounts'] as $account)
                                                <li>
                                                    <a href="{{ route('accounts') }}" class="group flex cursor-pointer select-none items-center gap-3 rounded-md px-3 py-2 text-sm text-foreground hover:bg-surface-elevated transition">
                                                        <x-lucide-wallet class="h-4 w-4 text-foreground-muted group-hover:text-primary transition" />
                                                        <span class="font-medium">{{ $account['name'] }}</span>
                                                        @if($account['institution'])
                                                            <span class="text-xs text-foreground-muted">&bull; {{ $account['institution'] }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Categorias -->
                                @if(count($results['categories']) > 0)
                                    <div>
                                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-foreground-muted">Categorias</h3>
                                        <ul class="mt-2 space-y-1">
                                            @foreach($results['categories'] as $category)
                                                <li>
                                                    <a href="{{ route('categories') }}" class="group flex cursor-pointer select-none items-center gap-3 rounded-md px-3 py-2 text-sm text-foreground hover:bg-surface-elevated transition">
                                                        <div class="h-3 w-3 rounded-full" style="background-color: {{ $category['color'] ?? '#ccc' }}"></div>
                                                        <span class="font-medium">{{ $category['name'] }}</span>
                                                        <x-ui.badge variant="{{ $category['type'] === 'income' ? 'success' : 'danger' }}" class="ml-2 text-[10px]">
                                                            {{ $category['type'] === 'income' ? 'Receita' : 'Despesa' }}
                                                        </x-ui.badge>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Cartões -->
                                @if(count($results['credit_cards']) > 0)
                                    <div>
                                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-foreground-muted">Cartões de Crédito</h3>
                                        <ul class="mt-2 space-y-1">
                                            @foreach($results['credit_cards'] as $card)
                                                <li>
                                                    <a href="#" class="group flex cursor-pointer select-none items-center gap-3 rounded-md px-3 py-2 text-sm text-foreground hover:bg-surface-elevated transition">
                                                        <x-lucide-credit-card class="h-4 w-4 text-foreground-muted group-hover:text-primary transition" />
                                                        <span class="font-medium">{{ $card['name'] }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Lançamentos -->
                                @if(count($results['transactions']) > 0)
                                    <div>
                                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-foreground-muted">Lançamentos</h3>
                                        <ul class="mt-2 space-y-1">
                                            @foreach($results['transactions'] as $transaction)
                                                <li>
                                                    <a href="{{ route('transactions') }}" class="group flex cursor-pointer select-none flex-col gap-1 rounded-md px-3 py-2 text-sm text-foreground hover:bg-surface-elevated transition">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center gap-2">
                                                                <x-lucide-receipt class="h-4 w-4 text-foreground-muted group-hover:text-primary transition" />
                                                                <span class="font-medium">{{ $transaction['description'] ?: 'Sem descrição' }}</span>
                                                            </div>
                                                            <span class="font-semibold {{ in_array($transaction['type'], ['income', 'refund']) ? 'text-success' : (in_array($transaction['type'], ['expense', 'payment']) ? 'text-destructive' : 'text-foreground') }}">
                                                                {{ in_array($transaction['type'], ['expense', 'payment']) ? '-' : '' }} R$ {{ number_format($transaction['amount'], 2, ',', '.') }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-2 text-xs text-foreground-muted pl-6">
                                                            <span>{{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('d/m/Y') }}</span>
                                                            <span>&bull;</span>
                                                            <span>{{ $transaction['account']['name'] ?? 'Sem conta' }}</span>
                                                            @if(isset($transaction['category']))
                                                                <span>&bull;</span>
                                                                <span class="flex items-center gap-1">
                                                                    <div class="h-2 w-2 rounded-full" style="background-color: {{ $transaction['category']['color'] ?? '#ccc' }}"></div>
                                                                    {{ $transaction['category']['name'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
                
                <!-- Helper footer -->
                <div class="flex items-center justify-between border-t border-border bg-surface-subtle px-4 py-3 sm:px-6">
                    <div class="flex flex-wrap items-center gap-4 text-xs text-foreground-muted">
                        <span class="flex items-center gap-1"><kbd class="rounded border border-border bg-surface px-1.5 font-sans font-medium text-foreground">Enter</kbd> para acessar</span>
                        <span class="flex items-center gap-1"><kbd class="rounded border border-border bg-surface px-1.5 font-sans font-medium text-foreground">Esc</kbd> para fechar</span>
                    </div>
                    <span class="text-xs text-foreground-muted">Busca Global</span>
                </div>
            </div>
        </div>
    </div>
</div>
