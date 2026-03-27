<x-filament-panels::page>
    <form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament::button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="authenticate"
            class="w-full"
        >
            <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="authenticate" />
            {{ __('filament-panels::pages/auth/login.form.actions.submit.label') }}
        </x-filament::button>

        @if (filament()->hasRegistration())
            <div class="text-sm text-center mt-4">
                {{ __('filament-panels::pages/auth/login.form.actions.register.before') }}

                <a href="{{ route('filament.auth.register') }}" wire:navigate>
                    {{ __('filament-panels::pages/auth/login.form.actions.register.label') }}
                </a>
            </div>
        @endif
    </form>
</x-filament-panels::page>