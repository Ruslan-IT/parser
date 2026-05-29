<x-filament::page>

    <x-filament::button wire:click="runParser">
        запустить парсер
    </x-filament::button>

    <div class="mt-5">
        {{ $output }}
    </div>
    <div>
       
    </div>

</x-filament::page>
