<x-jet-confirmation-modal wire:model="confirmingActivate">
    <x-slot name="title">
        {{ trans('Activated Account') }}
    </x-slot>

    <x-slot name="content">
        {{ trans('Are you sure you want to activated this account? ') }}
    </x-slot>

    <x-slot name="footer">
        <button wire:click="$set('confirmingActivate', false)" wire:loading.attr="disabled" class="btn btn-outline-secondary w-24 dark:border-dark-5 dark:text-gray-300 mr-1">
            {{ trans('global.cancel-button') }}
        </button>

        <button class="btn btn-danger w-24" wire:click="activated({{ $confirmingActivate }})" wire:loading.attr="disabled">
            {{ trans('global.yes-button') }}
        </button>
    </x-slot>
</x-jet-confirmation-modal>