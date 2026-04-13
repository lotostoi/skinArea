<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ModeratorPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ModeratorPanelProvider::class,
];
