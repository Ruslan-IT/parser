<x-filament::page>
    <div class="space-y-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-700">
            <strong>💡 Информация:</strong> Здесь отображаются только матчи со статусом «будущие» (ещё не сыгранные). Для них рассчитаны средние прогнозы на основе всех критериев. Если прогнозы не отображаются, запустите расчёт через кнопку на странице парсера.
        </div>
        {{ $this->table }}
    </div>
</x-filament::page>
