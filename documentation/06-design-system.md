# Дизайн-система SkinsArena

## Ориентиры

- **Кейсы:** [ggdrop.black](https://ggdrop.black) — тёмная тема, золотые акценты, карточки кейсов с тенями, анимации
- **Маркетплейс:** [lis-skins.com](https://lis-skins.com) — тёмная тема, карточки скинов с цветной тенью редкости, фильтры сбоку
- **Макеты дизайна:** нет. Стиль копируем с этих двух сайтов

---

## Общие принципы

- **Только тёмная тема** — светлой темы нет и не будет
- Фиксированная ширина контента: **~1400px**, по центру
- При сужении окна — горизонтальный скролл (не адаптивный)
- Скруглённые углы: `border-radius: 8px` (карточки), `12px` (модалки), `6px` (кнопки, инпуты)
- Тени: мягкие, с фиолетовым или синим подтоном

---

## Палитра цветов

### Фоны

| Токен | HEX | Где используется |
|-------|-----|-----------------|
| `bg-body` | `#0e0e12` | Основной фон страницы |
| `bg-surface` | `#1a1a24` | Карточки, панели, модалки |
| `bg-surface-hover` | `#22222e` | Hover на карточках |
| `bg-sidebar` | `#12121a` | Боковая панель, хедер |
| `bg-input` | `#16161e` | Поля ввода |
| `bg-elevated` | `#252530` | Выпадающие меню, тултипы |

### Акценты

| Токен | HEX | Где используется |
|-------|-----|-----------------|
| `primary` | `#f59e0b` (Amber 500) | Кнопки, ссылки, активные табы, акценты |
| `primary-hover` | `#d97706` (Amber 600) | Hover на primary-кнопках |
| `primary-light` | `#fbbf24` (Amber 400) | Подсветка, бейджи |
| `secondary` | `#8b5cf6` (Violet 500) | Вторичные акценты, тени карточек |
| `secondary-hover` | `#7c3aed` (Violet 600) | Hover на secondary-элементах |

### Статусы

| Токен | HEX | Где используется |
|-------|-----|-----------------|
| `success` | `#22c55e` | Успех, завершённые сделки, FN-износ |
| `warning` | `#f59e0b` | Предупреждения, ожидание |
| `danger` | `#ef4444` | Ошибки, отмена, BS-износ |
| `info` | `#3b82f6` | Информационные сообщения |

### Текст

| Токен | HEX | Где используется |
|-------|-----|-----------------|
| `text-primary` | `#e2e8f0` (Slate 200) | Основной текст |
| `text-secondary` | `#94a3b8` (Slate 400) | Вторичный текст, подписи |
| `text-muted` | `#64748b` (Slate 500) | Приглушённый текст, плейсхолдеры |
| `text-on-primary` | `#0e0e12` | Текст на primary-кнопках (тёмный на золотом) |

### Границы

| Токен | HEX | Где используется |
|-------|-----|-----------------|
| `border-default` | `#2a2a3a` | Границы карточек, инпутов |
| `border-hover` | `#3a3a4a` | Hover на границах |
| `border-focus` | `#f59e0b` | Focus на инпутах (primary) |

---

## Цвета редкости скинов CS2

Стандартные цвета CS2, совпадают на ggdrop и lis-skins:

| Редкость | Русское название | HEX | Tailwind-класс |
|----------|-----------------|-----|---------------|
| Consumer Grade | Ширпотреб | `#b0c3d9` | `rarity-consumer` |
| Industrial Grade | Промышленное | `#5e98d9` | `rarity-industrial` |
| Mil-Spec | Армейское | `#4b69ff` | `rarity-milspec` |
| Restricted | Запрещённое | `#8847ff` | `rarity-restricted` |
| Classified | Засекреченное | `#d32ce6` | `rarity-classified` |
| Covert | Тайное | `#eb4b4b` | `rarity-covert` |
| Contraband | Контрабанда | `#e4ae39` | `rarity-contraband` |

**Использование:** цвет тени карточки скина = цвет его редкости. Например: `box-shadow: 0 0 15px rgba(139, 71, 255, 0.3)` для Restricted.

---

## Цвета износа (Float / Wear)

| Износ | Сокращение | HEX | Float-диапазон |
|-------|-----------|-----|---------------|
| Factory New | FN | `#22c55e` (зелёный) | 0.00–0.07 |
| Minimal Wear | MW | `#16a34a` (тёмно-зелёный) | 0.07–0.15 |
| Field-Tested | FT | `#f59e0b` (оранжевый) | 0.15–0.38 |
| Well-Worn | WW | `#d97706` (тёмно-оранжевый) | 0.38–0.45 |
| Battle-Scarred | BS | `#ef4444` (красный) | 0.45–1.00 |

---

## Типографика

| Элемент | Размер | Вес | Шрифт |
|---------|--------|-----|-------|
| H1 (заголовок страницы) | 28px / 1.75rem | 700 (bold) | Inter |
| H2 (заголовок секции) | 22px / 1.375rem | 600 (semibold) | Inter |
| H3 (подзаголовок) | 18px / 1.125rem | 600 | Inter |
| Body (основной текст) | 14px / 0.875rem | 400 (regular) | Inter |
| Small (подписи) | 12px / 0.75rem | 400 | Inter |
| Price (цена на карточке) | 16px / 1rem | 700 | Inter |
| Price big (цена в деталях) | 24px / 1.5rem | 700 | Inter |

**Шрифт:** Inter (Google Fonts). Fallback: `system-ui, -apple-system, sans-serif`.

---

## Компоненты

### Кнопки

| Вариант | Фон | Текст | Border | Hover |
|---------|-----|-------|--------|-------|
| Primary | `#f59e0b` | `#0e0e12` | нет | `#d97706` |
| Secondary | `transparent` | `#e2e8f0` | `#2a2a3a` | `bg: #22222e` |
| Danger | `#ef4444` | `#ffffff` | нет | `#dc2626` |
| Ghost | `transparent` | `#94a3b8` | нет | `text: #e2e8f0` |

Скруглённые углы: `6px`. Padding: `8px 16px` (sm), `10px 20px` (md), `12px 24px` (lg).

### Карточка скина (ItemCard)

- Фон: `bg-surface` (`#1a1a24`)
- Border: `1px solid #2a2a3a`
- Border-radius: `8px`
- Тень: `box-shadow: 0 0 15px rgba(RARITY_COLOR, 0.25)` — цвет тени зависит от редкости
- Hover: `transform: scale(1.03)`, тень ярче (`0.4`)
- Внутри: изображение скина сверху, название, float-бар, цена снизу

### Карточка кейса (CaseCard)

- Аналогично ItemCard, но без float-бара
- Изображение кейса крупнее
- Цена по центру снизу, жёлтым (`primary`)

### Модальное окно

- Overlay: `rgba(0, 0, 0, 0.7)`
- Фон модалки: `bg-surface` (`#1a1a24`)
- Border-radius: `12px`
- Тень: `0 25px 50px rgba(0, 0, 0, 0.5)`

### Инпуты

- Фон: `bg-input` (`#16161e`)
- Border: `1px solid #2a2a3a`
- Focus border: `#f59e0b` (primary)
- Border-radius: `6px`
- Текст: `#e2e8f0`
- Placeholder: `#64748b`

---

## Tailwind-конфигурация

Все цвета выше должны быть добавлены в `tailwind.config.js`:

```js
colors: {
  body: '#0e0e12',
  surface: { DEFAULT: '#1a1a24', hover: '#22222e' },
  sidebar: '#12121a',
  elevated: '#252530',
  input: '#16161e',
  border: { DEFAULT: '#2a2a3a', hover: '#3a3a4a' },
  primary: { DEFAULT: '#f59e0b', hover: '#d97706', light: '#fbbf24' },
  secondary: { DEFAULT: '#8b5cf6', hover: '#7c3aed' },
  rarity: {
    consumer: '#b0c3d9',
    industrial: '#5e98d9',
    milspec: '#4b69ff',
    restricted: '#8847ff',
    classified: '#d32ce6',
    covert: '#eb4b4b',
    contraband: '#e4ae39',
  },
  wear: {
    fn: '#22c55e',
    mw: '#16a34a',
    ft: '#f59e0b',
    ww: '#d97706',
    bs: '#ef4444',
  },
}
```

---

## Иконки

Heroicons (встроены в Filament и Tailwind UI). Стиль: `outlined` для навигации, `solid` для действий.

---

## Анимации

- Hover на карточках: `transition: transform 0.2s ease, box-shadow 0.2s ease`
- Рулетка кейсов: горизонтальная лента, CSS `transform: translateX()` с `ease-out`
- Появление модалок: `opacity 0→1` + `translateY(10px→0)`, 200ms
- Уведомления (toast): slide-in справа, 300ms
