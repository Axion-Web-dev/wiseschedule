<x-filament-panels::page>
    <form wire:submit="register">
        {{ $this->form }}

        <x-filament::button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="register"
            class="w-full"
        >
            <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="register" />
            {{ __('filament-panels::pages/auth/register.form.actions.submit.label') }}
        </x-filament::button>

        <div class="text-sm text-center mt-4">
            {{ __('filament-panels::pages/auth/register.form.actions.login.before') }}

            <a href="{{ route('filament.auth.login') }}" wire:navigate>
                {{ __('filament-panels::pages/auth/register/form.actions.login.label') }}
            </a>
        </div>
    </form>
</x-filament-panels::page>