# Аудит фронтенда SkinsArena (Vue 3) — причины «не с первого раза» и правила

Документ фиксирует пройденный разбор `resources/js/`, найденные классы багов и **обязательные паттерны** для новых экранов, чтобы не повторять гонки данных и пустые блоки.

---

## 1. Сессия и порядок монтирования

### Что ломалось

- **`user` в Pinia появлялся позже токена**: `isAuthenticated = !!token && !!user`. Роутер пускал по `localStorage`, а дети (`ProfilePage` и т.д.) монтировались **раньше**, чем `App` успевал вызвать `loadUser()` в `onMounted` (у родителя `onMounted` идёт **после** детей).
- **Итог**: `v-if="auth.user"` → пустой кабинет до F5 или случайного повторного рендера.

### Что сделано в коде

1. **`resources/js/app.ts`** — после `app.use(pinia)` и **до** `router` + `mount`: при наличии токена и отсутствии `user` вызывается `await auth.loadUser()`.
2. **`resources/js/router/index.ts`** — для `meta.requiresAuth`: при токене без `user` — `await loadUser()` в `beforeEach`; без пользователя после этого — редирект на главную.
3. **`env.d.ts`** — типизация `RouteMeta.requiresAuth`.

### Правило для новых фич

- Любой экран, который зависит от **`auth.user`**, не должен рассчитывать на «сам догрузится в `onMounted` у `App`». Либо маршрут с **`requiresAuth`** (ожидание в guard), либо явное ожидание/скелетон в компоненте.

---

## 2. Вкладки и флаги `active` / `enabled`

### Что ломалось

- Панели внутри кабинета монтируются **сразу** (часто под `v-show`), а данные грузились в `onMounted` только если `active === true`.
- Дополнительно на «Транзакциях» / «Сделках» стояло условие вида **`items.length === 0 && !loading`** — при гонке первый запрос мог **не уйти**, блок оставался пустым.

### Что сделано в коде

- Единый паттерн: **`watch(() => props.active | enabled, (v) => { if (v) void load() }, { immediate: true })`**, без дублирующего `onMounted` с той же логикой.
- Файлы: `ProfileTransactionsTab`, `ProfileDealsTab`, `SteamInventoryPanel`, `ActiveListingsPanel`, `ProfileSoldPanel`.

### Правило для новых блоков

1. Если блок **не всегда виден** (`v-show`, вкладка, `enabled`) и грузит API — **не** полагаться только на `onMounted` при `enabled: true` при первом монтировании (часто первый рендер с `enabled: false`).
2. **Не** связывать первый `load()` с `!loading && items.length === 0` — это ломается при параллельных обновлениях. Для «обновить при каждом открытии вкладки» — достаточно `if (v) load()`; для кэша с TTL — отдельный флаг `lastFetchedAt`, а не `loading`.

---

## 3. Email и trade URL после входа

Отдельная модалка после Steam не используется: данные вводятся в **`ProfileCabinetTopCard`** и на вкладке **«Настройки»** (`ProfileSettingsTab`), черновики синхронизируются через **`useProfileTradeAndEmail`** (`watch` на `auth.user`).

---

## 4. Composable `useProfileTradeAndEmail`

- Черновики email / trade URL — **модульные** `ref` + один глобальный `watch` на `auth.user` (чтобы не вешать несколько одинаковых watcher’ов). Второй и последующие вызовы composable делают `syncDraftsFromUser(auth.user)` вручную.
- **Правило**: новые поля профиля либо расширяют этот composable осознанно, либо заводят отдельный composable без дублирования глобального состояния «на два экрана».

---

## 5. Axios и 401

- **`resources/js/utils/api.ts`**: при 401 (кроме обмена Steam) — очистка `localStorage` и **`window.location.href = '/'`**. Это полный перезагрузочный выход из SPA.
- **Правило**: не слать параллельно «опциональные» запросы без токена на защищённые эндпоинты в момент логаута; учитывать жёсткий редирект при отладке кабинета.

---

## 6. Страницы без критичных гонок (кратко)

| Файл | Заметка |
|------|---------|
| `MarketPage.vue` | Одна загрузка в `onMounted` — страница публичная, ок. |
| `SupportPage.vue` | Список в `onMounted` — маршрут под `requiresAuth`, `user` уже есть к монту. Список тикетов: `loadingList` → пустой текст → `ul` (без пустого `ul` при 0 записей). |
| `HomePage.vue` | Статика/моки, без API в монте. |
| `AuthSteamCompletePage.vue` | Обмен кода → запись `user` в store, ок. |
| `ListForSaleModal.vue` | `watch` на `open` сбрасывает форму — ок. |
| `MainLayout.vue` | Welcome-флаг от `auth` — после фикса сессии ок. |
| `AppHeader.vue` | `isAuthenticated && auth.user` — после bootstrap/guard консистентно. |
| `stores/balance.ts` | Подтягивается из `loadUser` / `exchangeCode`; отдельно при ошибке `/balance` — fallback. |

---

## 7. Цепочки `v-if` / `v-else-if` / `v-else` и **loading**

Ошибка: при `loading === true` условие вида `v-else-if="!loading && items.length === 0"` ложно, срабатывает **`v-else`** (например, сетка под `v-for` по пустому массиву) — пользователь видит **пустой блок без спиннера**, хотя запрос ещё идёт. После F5 ответ часто быстрее → кажется, что «помогла только перезагрузка».

**Правило:** явная ветка **`v-if="loading"`** (или skeleton) **перед** ветками ошибки и пустого списка. Тот же порядок: `ActiveListingsPanel.vue`, `ProfileSoldPanel.vue`, `SteamInventoryPanel.vue`; в списках поддержки — `SupportPage.vue` (сначала загрузка, затем пусто, затем список).

---

## 8. Чеклист перед мержем нового UI

- [ ] Есть ли **скрытые по флагу** блоки с API? → `watch` + `immediate`, не только `onMounted`.
- [ ] Защищённый маршрут? → `meta.requiresAuth` + guard (или явная гидратация до рендера).
- [ ] Модалка/форма от **`auth.user`/`auth.*`**? → `watch` на источник, не разовый `onMounted`.
- [ ] Условие «загрузить если пусто и не loading»? → пересмотреть; предпочтительно явный смысл (первая загрузка / refetch / stale).
- [ ] Новый вызов API из нескольких сестринских компонентов? → нет ли двойных запросов и гонок с 401.
- [ ] Есть `loading` и цепочка `v-if` / `v-else`? → отдельная ветка **`v-if="loading"`** до пустого состояния и сетки, иначе при загрузке попадёшь в `v-else` с пустым списком.

---

## 9. Связанные файлы (точки входа)

- `resources/js/app.ts` — bootstrap сессии  
- `resources/js/router/index.ts` — guard  
- `resources/js/stores/auth.ts` — `loadUser`, `token`, `user`  
- `resources/js/utils/api.ts` — интерсепторы  

Обновляй этот раздел при смене архитектуры авторизации (например, refresh-токены).
