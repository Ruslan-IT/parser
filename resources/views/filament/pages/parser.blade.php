<x-filament::page>
    <div class="space-y-6">

        {{-- Блок настройки парсинга --}}
        <x-filament::fieldset label="Настройки парсинга" class="p-4">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ссылки на страницы турниров (каждая с новой строки)
                    </label>
                    <textarea style="width: 100%" wire:model="urls" rows="5" placeholder="https://www.betexplorer.com/football/brazil/serie-b/
https://www.betexplorer.com/football/brazil/serie-b-2025/
https://www.betexplorer.com/football/brazil/serie-b-2024/" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono text-sm"></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Укажите по одной ссылке на каждый сезон. Например, для сезона 2026 используйте ссылку без года, для остальных – с годом в конце.
                    </p>
                </div>
            </div>
        </x-filament::fieldset>

        {{-- Этап 1: Сбор данных --}}
        <x-filament::fieldset label="⚙️ Этап 1. Сбор данных" class="p-4">
            <div class="border rounded-lg p-4 mb-6">

                {{-- Кнопка 1 --}}
                <br>
                <div>
                    <x-filament::button wire:click="runParser" wire:loading.attr="disabled">
                        📥 Запустить парсер
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> Загружает страницы турниров (ссылки выше), извлекает все матчи, даты, команды, счета и коэффициенты 1X2.
                        Сохраняет в таблицу <code>Матчы</code>.<br>
                        <strong>Где смотреть:</strong> Внизу страницы в таблице «Последние добавленные матчи» или в разделе <strong>«Матчи»</strong> в левом меню.<br>
                        <strong>Как проверить:</strong> После нажатия появится сообщение «✅ Успешно сохранено матчей: X».
                    </div>
                </div>
                <br>

                {{-- Кнопка 2 --}}
              {{--  <div class="border rounded-lg p-4 mb-6 bg-gray-50">
                    <x-filament::button wire:click="collectAh" wire:loading.attr="disabled">
                        ⚽ Собрать AH
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> Для матчей без азиатских фор загружает страницу с вкладкой #ah, находит равновесную и покупную линии (целые индексы) и сохраняет в таблицу <code>asian_handicaps</code>.<br>
                        <strong>Где смотреть:</strong> В разделе <strong>«Азиатские форы»</strong> в левом меню.<br>
                        <strong>Как проверить:</strong> После выполнения появится сообщение «✅ Сбор AH завершён».
                    </div>
                </div>--}}

                {{-- Кнопка 3 --}}
                <div>
                    <x-filament::button wire:click="collectAhBatch" wire:loading.attr="disabled">
                        ⚡ Собрать AH пакетами
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> Для матчей без азиатских фор загружает страницу с вкладкой #ah, находит равновесную и покупную линии (целые индексы) и сохраняет в таблицу <code>Азиатские форы</code>.<br>
                        <strong>Где смотреть:</strong> В разделе <strong>«Азиатские форы»</strong> в левом меню.<br>
                        <strong>Как проверить:</strong> После выполнения появится сообщение «✅ Сбор AH завершён».
                    </div>
                </div>

            </div>

            <div wire:loading wire:target="runParser" class="mt-2 text-sm text-gray-500">Идёт парсинг матчей...</div>
            <div wire:loading wire:target="collectAh" class="mt-2 text-sm text-gray-500">Идёт сбор азиатских фор...</div>
            <div wire:loading wire:target="collectAhBatch" class="mt-2 text-sm text-gray-500">Идёт пакетный сбор азиатских фор...</div>
        </x-filament::fieldset>

        {{-- Этап 2: Расчёт статистики --}}
        <x-filament::fieldset label="📊 Этап 2. Расчёт статистики" class="p-4">
            <div class="space-y-4">
                <br>
                {{-- Кнопка 4 --}}
                <div>
                    <x-filament::button wire:click="calculateStats" wire:loading.attr="disabled">
                        📈 Обновить статистику команд
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">

                        <strong>Что делает:</strong> На основе завершённых матчей вычисляет для каждой команды в разрезе лиги/сезона: матчи, очки, голы, разницу мячей, форму (последние 5 матчей).
                        Сохраняет в таблицу {{--<code>team_season_stats</code>--}} <code>Статистику команд</code>.<br>

                        <strong>Где смотреть:</strong> В разделе <strong>«Статистика команд»</strong> в левом меню.<br>
                        <strong>Как проверить:</strong> После выполнения появится сообщение «✅ Статистика обновлена!».
                    </div>
                </div>
                <br>

                {{-- Кнопка 5 --}}
                <br>
                <div>
                    <x-filament::button wire:click="calculateCriteria" wire:loading.attr="disabled">
                        📋 Рассчитать критерии 1–5
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> Для каждого завершённого матча вычисляет 5 критериев: разница очков (общая, полевая, последние 5 матчей, последние 5 с учётом поля) и процент прохода форы.
                        Сохраняет в таблицу{{-- <code>criteria_values</code>--}}. <code>Расчет критериев</code>
                        <br>
                        <strong>Где смотреть:</strong> В разделе <strong>«Критерии»</strong> в левом меню.<br>
                        <strong>Как проверить:</strong> После выполнения сообщение «✅ Сохранено критериев для X матчей».
                    </div>
                </div>

                {{-- Кнопка 6 --}}
                <br>
                <div>
                    <x-filament::button wire:click="calculatePoisson" wire:loading.attr="disabled">
                        ⚽ Рассчитать критерий Пуассона
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> На основе статистики команд вычисляет силу атаки и обороны, ожидаемые голы (M1, M2), применяет распределение Пуассона и получает вероятности исходов.
                        Сохраняет как критерий №6 в {{--<code>match_predictions</code> --}}<code>Расчёт вероятностей и эффективностей</code>.<br>

                        <strong>Где смотреть:</strong> В разделе <strong>«Расчёт вероятностей и эффективностей»</strong>, отфильтруйте по <strong>Критерий = Кр6</strong>.<br>
                        <strong>Как проверить:</strong> После выполнения сообщение «✅ Критерий Пуассона рассчитан».
                    </div>
                </div>

            </div>
        </x-filament::fieldset>

        {{-- Этап 3: Вероятности и прогнозы --}}
        <x-filament::fieldset label="🎯 Этап 3. Вероятности и прогнозы" class="p-4">
            <div class="space-y-4">

                {{-- Кнопка 7 --}}
                <br>
                <div>
                    <x-filament::button wire:click="calculateProbabilities" wire:loading.attr="disabled">
                        🎲 Рассчитать вероятности
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> Для каждого критерия (1–6) находит исторические матчи с похожим значением этого критерия и вычисляет частоты исходов (вероятности). Умножает на коэффициенты, получая эффективности. Сохраняет в <code>match_predictions</code> с <code>criteria_id = 1..6</code>.<br>
                        <strong>Где смотреть:</strong> В разделе <strong>«Прогнозы»</strong> (все записи с критериями).<br>
                        <strong>Как проверить:</strong> После выполнения сообщение «✅ Сохранено прогнозов для X матчей».
                    </div>
                </div>

                {{-- Кнопка 8 --}}
                <br>
                <div>
                    <x-filament::button wire:click="recalculateAverages" wire:loading.attr="disabled">
                        📊 Пересчитать средние значения
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> Для каждого матча вычисляет средние вероятности и эффективности по всем критериям. Сохраняет одну строку с <code>is_average = true</code> в <code>match_predictions</code>.<br>
                        <strong>Где смотреть:</strong> В разделе <strong>«Прогнозы»</strong>, отфильтруйте по <strong>Критерий = Среднее</strong>.<br>
                        <strong>Как проверить:</strong> После выполнения сообщение «✅ Обновлены средние для X матчей».
                    </div>
                </div>

            </div>
        </x-filament::fieldset>

        {{-- Этап 4: Экспорт --}}
        <x-filament::fieldset label="📄 Этап 4. Экспорт результатов" class="p-4">
            <div class="space-y-4">

                {{-- Кнопка 9 --}}
                <br>
                <div>
                    {{-- <x-filament::button wire:click="exportCsv" wire:loading.attr="disabled">
                        📑 Экспорт CSV
                    </x-filament::button> --}}
                    <x-filament::button wire:click="exportExcel" wire:loading.attr="disabled">
                        📗 Экспорт Excel
                    </x-filament::button>
                    <div class="text-sm text-gray-600 mt-1">
                        <strong>Что делает:</strong> Генерирует Excel-файл с прогнозами (средние вероятности и эффективности) для всех матчей. Файл включает дату, лигу, команды, коэффициенты, вероятности, эффективности и итоговый прогноз. Файл автоматически скачивается.<br>
                        <strong>Где смотреть:</strong> Файл сохраняется на сервере и сразу скачивается в браузер.<br>
                        <strong>Как проверить:</strong> После нажатия откроется окно загрузки .xlsx файла.
                    </div>
                </div>

            </div>
        </x-filament::fieldset>

        {{-- Сервисные кнопки --}}
        <br>
        <x-filament::fieldset label="🧹 Сервис" class="p-4">
            <div class="flex flex-wrap gap-3">
                <x-filament::button wire:click="clearStats" color="danger" size="sm">Очистить статистику</x-filament::button>
                <x-filament::button wire:click="clearCriteria" color="danger" size="sm">Очистить критерии</x-filament::button>
                <x-filament::button wire:click="clearPredictions" color="danger" size="sm">Очистить прогнозы</x-filament::button>
                <x-filament::button wire:click="clearHandicaps" color="danger" size="sm">Очистить AH</x-filament::button>
                <x-filament::button wire:click="clearAll" color="danger" size="sm">🔥 Очистить всё</x-filament::button>
            </div>
            <div class="text-sm text-gray-600 mt-2">
                <strong>Предназначение:</strong> Удаляют соответствующие расчётные данные (статистику, критерии, прогнозы, азиатские форы). Кнопка «Очистить всё» удаляет все перечисленные данные, но <strong>не удаляет</strong> матчи.
            </div>
        </x-filament::fieldset>

        {{-- Вывод сообщений --}}
        <br>
        @if($output)
            <div class="p-4 rounded-md {{ str_contains($output, '✅') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                {!! $output !!}
            </div>
        @endif

        {{-- Таблица последних матчей --}}
        <br>
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
                                <td class="py-3 px-6">{{ $match['home_score'] ?? '?' }} : {{ $match['away_score'] ?? '?' }}</td>
                                <td class="py-3 px-6">{{ $match['match_date'] ? \Carbon\Carbon::parse($match['match_date'])->format('d.m.Y') : '—' }}</td>
                                <td class="py-3 px-6">{{ $match['odd_home'] ?? '—' }} | {{ $match['odd_draw'] ?? '—' }} | {{ $match['odd_away'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">Нет данных. Введите URL и нажмите «Запустить парсер».</p>
            @endif
        </div>
        <br>
        <div class="mt-4">
            <a href="{{ url('/admin/match-games') }}" class="text-primary-600 hover:underline">
                🔗 Перейти ко всем матчам →
            </a>
        </div>

    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('download-csv', (event) => {
                const url = event[0].url;
                const link = document.createElement('a');
                link.href = url;
                link.download = url.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</x-filament::page>
