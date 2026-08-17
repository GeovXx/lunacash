<x-ui.card>
    <h1 class="text-h2 font-semibold text-foreground">Entrar no LunaCash</h1>
    <p class="mt-1 text-sm text-foreground-muted">Acesse sua conta para continuar.</p>

    <form wire:submit="authenticate" class="mt-6 space-y-4">
        <div>
            <x-forms.label for="email" required>E-mail</x-forms.label>
            <x-forms.input wire:model="email" id="email" type="email" name="email" autocomplete="username" required class="mt-1.5" />
            <x-forms.error name="email" />
        </div>

        <div>
            <x-forms.label for="password" required>Senha</x-forms.label>
            <x-forms.input wire:model="password" id="password" type="password" name="password" autocomplete="current-password" required class="mt-1.5" />
            <x-forms.error name="password" />
        </div>

        <x-forms.checkbox wire:model="remember">Manter conectado</x-forms.checkbox>

        <x-ui.button type="submit" class="w-full">Entrar</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-foreground-muted">
        Não tem conta?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-primary hover:text-primary-hover">Cadastre-se</a>
    </p>
</x-ui.card>
