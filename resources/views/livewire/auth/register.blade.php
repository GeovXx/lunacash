<x-ui.card>
    <h1 class="text-h2 font-semibold text-foreground">Criar conta no LunaCash</h1>
    <p class="mt-1 text-sm text-foreground-muted">Preencha os dados abaixo para começar.</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <x-forms.label for="name" required>Nome</x-forms.label>
            <x-forms.input wire:model="name" id="name" type="text" name="name" autocomplete="name" required class="mt-1.5" />
            <x-forms.error name="name" />
        </div>

        <div>
            <x-forms.label for="email" required>E-mail</x-forms.label>
            <x-forms.input wire:model="email" id="email" type="email" name="email" autocomplete="username" required class="mt-1.5" />
            <x-forms.error name="email" />
        </div>

        <div>
            <x-forms.label for="password" required>Senha</x-forms.label>
            <x-forms.input wire:model="password" id="password" type="password" name="password" autocomplete="new-password" required class="mt-1.5" />
            <x-forms.error name="password" />
        </div>

        <div>
            <x-forms.label for="password_confirmation" required>Confirmar senha</x-forms.label>
            <x-forms.input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="mt-1.5" />
        </div>

        <x-ui.button type="submit" class="w-full">Criar conta</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-foreground-muted">
        Já tem conta?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary hover:text-primary-hover">Entrar</a>
    </p>
</x-ui.card>
