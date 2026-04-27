<x-filament-panels::page>
    @if ($level)
        <div class="mb-4 rounded-xl border border-custom-200 bg-custom-50 px-5 py-3 text-sm text-custom-700 dark:border-custom-700 dark:bg-custom-900 dark:text-custom-300"
             style="--c-50:var(--primary-50);--c-200:var(--primary-200);--c-700:var(--primary-700);--c-900:var(--primary-900);--c-300:var(--primary-300)">
            <strong>Уровень:</strong> {{ $level->name }}
            &nbsp;·&nbsp;
            <strong>Кейс:</strong> {{ $level->gameCase?->name ?? '—' }}
            &nbsp;·&nbsp;
            <strong>Шанс:</strong> {{ number_format((float) $level->chance, 4) }}%
            &nbsp;·&nbsp;
            <strong>Призов уже добавлено:</strong> {{ $level->items()->count() }}
        </div>

        <div class="mb-4 rounded-xl border border-warning-200 bg-warning-50 px-5 py-3 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-900/30 dark:text-warning-300">
            Выберите нужный скин и нажмите <strong>«Добавить в уровень»</strong> прямо на карточке.
            Используйте фильтры и поиск для быстрого подбора.
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
