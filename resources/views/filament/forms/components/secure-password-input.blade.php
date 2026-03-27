@php
    $id = $getId();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isRequired = $isRequired();
    $placeholder = $getPlaceholder();
    $label = $getLabel();
    $helperText = $getHelperText();
    $hint = $getHint();
    $hintColor = $getHintColor();
    $hintIcon = $getHintIcon();
@endphp

<x-filament::field.wrapper
    :id="$id"
    :label="$label"
    :label-sr-only="$isLabelSrOnly()"
    :helper-text="$helperText"
    :hint="$hint"
    :hint-color="$hintColor"
    :hint-icon="$hintIcon"
    :required="$isRequired"
    :state-path="$statePath"
>
    <x-filament::field.input.wrapper
        :id="$id"
        :disabled="$isDisabled"
        :has-prefix-and-suffix="false"
        :inline-prefix="false"
        :inline-suffix="false"
        :state-path="$statePath"
    >
        <x-filament::field.input
            :id="$id"
            {{ $attributes->merge([
                'type' => 'password',
                'wire:model' => $statePath,
                'placeholder' => $placeholder,
                'disabled' => $isDisabled,
                'required' => $isRequired,
                'class' => 'fi-input',
            ]) }}
        />
        
        <x-filament::field.input.prefix-action
            action="passwordReveal"
            :label="__('filament::forms/components/password-input.reveal.label')"
            :state-path="$statePath"
        />
    </x-filament::field.input.wrapper>
</x-filament::field.wrapper>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[type="password"][wire\\:model*="password"]');
    
    passwordInputs.forEach(function(input) {

        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'mt-2 h-2 bg-gray-200 rounded-full overflow-hidden';
        strengthIndicator.innerHTML = '<div class="h-full transition-all duration-300" style="width: 0%"></div>';
        
        input.parentNode.parentNode.appendChild(strengthIndicator);
        
        const strengthBar = strengthIndicator.querySelector('div');
        
        input.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            strengthBar.style.width = strength.percentage + '%';
            strengthBar.className = 'h-full transition-all duration-300 ' + strength.color;
        });
    });
    
    function calculatePasswordStrength(password) {
        if (!password) return { percentage: 0, color: 'bg-gray-300' };
        
        let score = 0;

        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;

        if (/[a-z]/.test(password)) score += 15;
        if (/[A-Z]/.test(password)) score += 15;
        if (/[0-9]/.test(password)) score += 15;
        if (/[^a-zA-Z0-9]/.test(password)) score += 15;

        if (password.length >= 16) score += 10;
        if (!/(.)\1{2,}/.test(password)) score += 10; // No repeated characters
        
        let percentage = Math.min(score, 100);
        let color = 'bg-red-500';
        
        if (percentage >= 80) color = 'bg-green-500';
        else if (percentage >= 60) color = 'bg-yellow-500';
        else if (percentage >= 40) color = 'bg-orange-500';
        
        return { percentage, color };
    }
});
</script>
@endpush