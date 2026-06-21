<x-filament::page>
    <div class="space-y-6">
        {{-- Блок настройки парсинга --}}
        <x-filament::fieldset label="Настройки парсинга" class="p-4">
            <div class="space-y-4">
                {{-- Поле для ссылки --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ссылки на страницы турниров (каждая с новой строки)
                    </label>
                    <textarea style="width: 100%" wire:model="urls"
                              rows="5"
                              placeholder="https://www.betexplorer.com/football/brazil/serie-b/&#10;https://www.betexplorer.com/football/brazil/serie-b-2025/&#10;https://www.betexplorer.com/football/brazil/serie-b-2024/"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono text-sm"></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Укажите по одной ссылке на каждый сезон. Например, для сезона 2026 используйте ссылку без года, для остальных – с годом в конце.
                    </p>
                </div>

                <br>

                {{-- Кнопка запуска --}}
                <div>
                    <x-filament::button wire:click="runParser" wire:loading.attr="disabled">
                        {{ __('Запустить парсер') }}
                    </x-filament::button>
                    <div wire:loading wire:target="runParser" class="mt-2 text-sm text-gray-500">
                        Идёт парсинг и сохранение данных...
                    </div>
                </div>


                <div class="mt-4">
                    <x-filament::button wire:click="collectAh" wire:loading.attr="disabled">
                        {{ __('Собрать азиатские форы (AH)') }}
                    </x-filament::button>
                    <div wire:loading wire:target="collectAh" class="mt-2 text-sm text-gray-500">
                        Идёт сбор азиатских фор, это может занять некоторое время...
                    </div>
                </div>


            </div>
        </x-filament::fieldset>

        {{-- Вывод сообщения об успехе или ошибке --}}
        @if($output)
            <div class="p-4 rounded-md {{ str_contains($output, '✅') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                {{ $output }}
            </div>
        @endif

        {{-- Таблица последних сохранённых матчей --}}
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">📋 Последние добавленные матчи</h3>

            @if(count($matchesList) > 0)
                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="py-3 px-6">Лига</th>
                            <th class="py-3 px-6">Хозяева</th>
                            <th class="py-3 px-6">Гости</th>
                            <th class="py-3 px-6">Счёт</th>
                            <th class="py-3 px-6">Дата</th>
                            <th class="py-3 px-6">Коэффициенты (1X2)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($matchesList as $match)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="py-3 px-6">{{ $match['league']['name'] ?? '—' }}</td>
                                <td class="py-3 px-6 font-medium text-gray-900">{{ $match['home_team']['name'] ?? '—' }}</td>
                                <td class="py-3 px-6">{{ $match['away_team']['name'] ?? '—' }}</td>
                                <td class="py-3 px-6">
                                    {{ $match['home_score'] ?? '?' }} : {{ $match['away_score'] ?? '?' }}
                                </td>
                                <td class="py-3 px-6">{{ $match['match_date'] ? \Carbon\Carbon::parse($match['match_date'])->format('d.m.Y') : '—' }}</td>
                                <td class="py-3 px-6">
                                    @php $odds = json_decode($match['odds_json'], true); @endphp
                                    {{ $odds ? implode(' | ', $odds) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">Нет данных. Введите URL и нажмите «Запустить парсер».</p>
            @endif
        </div>

        {{-- Ссылка на полный список матчей --}}
        <div class="mt-4">
            <a href="{{ url('/admin/match-games') }}" class="text-primary-600 hover:underline">
                🔗 Перейти ко всем матчам →
            </a>
        </div>
    </div>
</x-filament::page>
