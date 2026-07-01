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

            </div>
        </x-filament::fieldset>

        <x-filament::fieldset label="🚀  Сбор данных" class="p-4">

            <div class="flex flex-wrap gap-3">

                <x-filament::button wire:click="runParser" wire:loading.attr="disabled">
                    📥 Запустить парсер
                </x-filament::button>

                <x-filament::button wire:click="collectAh" wire:loading.attr="disabled">
                    ⚽ Собрать AH
                </x-filament::button>

                <x-filament::button wire:click="collectAhBatch" wire:loading.attr="disabled">
                    ⚡ Собрать AH пакетами
                </x-filament::button>

            </div>

            <div wire:loading wire:target="runParser" class="mt-2 text-sm text-gray-500">
                Идёт парсинг матчей...
            </div>

            <div wire:loading wire:target="collectAh" class="mt-2 text-sm text-gray-500">
                Идёт сбор азиатских фор...
            </div>

            <div wire:loading wire:target="collectAhBatch" class="mt-2 text-sm text-gray-500">
                Идёт пакетный сбор азиатских фор...
            </div>

        </x-filament::fieldset>


        <x-filament::fieldset label="📊  Расчёт статистики" class="p-4">

            <div class="flex flex-wrap gap-3">

                <x-filament::button wire:click="calculateStats" wire:loading.attr="disabled">
                    📈 Обновить статистику команд
                </x-filament::button>

                <x-filament::button wire:click="calculateCriteria" wire:loading.attr="disabled">
                    📋 Рассчитать критерии 1–5
                </x-filament::button>

                <x-filament::button wire:click="calculatePoisson" wire:loading.attr="disabled">
                    ⚽ Рассчитать критерий Пуассона
                </x-filament::button>

            </div>

        </x-filament::fieldset>


        <x-filament::fieldset label="🎯  Вероятности и прогнозы" class="p-4">

            <div class="flex flex-wrap gap-3">

                <x-filament::button wire:click="calculateProbabilities" wire:loading.attr="disabled">
                    🎲 Рассчитать вероятности
                </x-filament::button>

                <x-filament::button wire:click="recalculateAverages" wire:loading.attr="disabled">
                    📊 Пересчитать средние значения
                </x-filament::button>

            </div>

        </x-filament::fieldset>


        <x-filament::fieldset label="📄 Экспорт результатов" class="p-4">

            <div class="flex flex-wrap gap-3">

              {{--  <x-filament::button wire:click="exportCsv" wire:loading.attr="disabled">
                    📑 Экспорт CSV
                </x-filament::button>--}}

                <x-filament::button wire:click="exportExcel" wire:loading.attr="disabled">
                    📗 Экспорт Excel
                </x-filament::button>

            </div>

        </x-filament::fieldset>


        <x-filament::fieldset label="🧹 Сервис" class="p-4">

            <div class="flex flex-wrap gap-3">

                <x-filament::button
                    wire:click="clearStats"
                    color="danger"
                    size="sm">
                    Очистить статистику
                </x-filament::button>

                <x-filament::button
                    wire:click="clearCriteria"
                    color="danger"
                    size="sm">
                    Очистить критерии
                </x-filament::button>

                <x-filament::button
                    wire:click="clearPredictions"
                    color="danger"
                    size="sm">
                    Очистить прогнозы
                </x-filament::button>

                <x-filament::button
                    wire:click="clearHandicaps"
                    color="danger"
                    size="sm">
                    Очистить AH
                </x-filament::button>

                <x-filament::button
                    wire:click="clearAll"
                    color="danger"
                    size="sm">
                    🔥 Очистить всё
                </x-filament::button>

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
                                    {{ $match['odd_home'] ?? '—' }} | {{ $match['odd_draw'] ?? '—' }} | {{ $match['odd_away'] ?? '—' }}
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

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('download-csv', (event) => {
                const url = event[0].url;
                // Создаём невидимую ссылку и кликаем по ней (обход блокировки всплывающих окон)
                const link = document.createElement('a');
                link.href = url;
                link.download = url.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>

