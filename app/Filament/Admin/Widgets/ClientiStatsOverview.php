<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Cliente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ClientiStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected function getStats(): array
    {
        $totalClienti = Cliente::count();
        $clientiRecenti = Cliente::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $ultimoCliente = Cliente::latest()->first();
        
        return [
            Stat::make('Totale Clienti', $totalClienti)
                ->description('Numero totale di clienti')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            
            Stat::make('Clienti Recenti', $clientiRecenti)
                ->description('Aggiunti negli ultimi 30 giorni')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            
            Stat::make('Ultimo Cliente', $ultimoCliente ? $ultimoCliente->nome : 'Nessuno')
                ->description($ultimoCliente && $ultimoCliente->created_at ? 'Aggiunto il ' . $ultimoCliente->created_at->format('d/m/Y') : '')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
        ];
    }
}
