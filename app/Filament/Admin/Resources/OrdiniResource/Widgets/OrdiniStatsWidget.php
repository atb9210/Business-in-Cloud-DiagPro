<?php

namespace App\Filament\Admin\Resources\OrdiniResource\Widgets;

use App\Models\Ordini;
use App\Models\Integrazione;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class OrdiniStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        // Usa il mese corrente come periodo di default
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Query base per il periodo selezionato
        $ordiniQuery = Ordini::whereBetween('data', [$startDate, $endDate]);
        
        // Calcoli statistici
        $totaleOrdini = $ordiniQuery->count();
        $totaleVenduto = $ordiniQuery->sum('prezzo_vendita');
        
        // Calcolo del profitto (margine totale)
        $totaleProfitto = $ordiniQuery->get()->sum(function ($ordine) {
            return ($ordine->prezzo_vendita ?? 0) - (($ordine->costo_marketing ?? 0) + ($ordine->costo_prodotto ?? 0) + ($ordine->costo_spedizione ?? 0) + ($ordine->altri_costi ?? 0));
        });
        
        // Calcolo del margine percentuale
        $marginePercentuale = $totaleVenduto > 0 ? ($totaleProfitto / $totaleVenduto) * 100 : 0;
        
        // Obiettivo configurabile dalle impostazioni
        $obiettivo = Integrazione::get('obiettivo_profitto_mensile', 2000);
        
        // Percentuale obiettivo
        $percentualeObiettivo = $obiettivo > 0 ? ($totaleProfitto / $obiettivo) * 100 : 0;
        
        return [
            Stat::make('Totale Ordini', number_format($totaleOrdini))
                ->description('Ordini del mese corrente')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
                
            Stat::make('Totale Venduto', '€ ' . number_format($totaleVenduto, 2, ',', '.'))
                ->description('Fatturato del periodo')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Profitto', '€ ' . number_format($totaleProfitto, 2, ',', '.'))
                ->description('Profitto netto del periodo')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color($totaleProfitto > 0 ? 'success' : 'danger'),
                
            Stat::make('Margine %', number_format($marginePercentuale, 1) . '%')
                ->description('Percentuale di profitto')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($marginePercentuale >= 20 ? 'success' : ($marginePercentuale >= 10 ? 'warning' : 'danger')),
                
            Stat::make('Obiettivo', '€' . number_format($obiettivo, 0, ',', '.') . ' (' . number_format($percentualeObiettivo, 1) . '%)')
                ->description(number_format($percentualeObiettivo, 1) . '% dell\'obiettivo raggiunto')
                ->descriptionIcon($percentualeObiettivo >= 100 ? 'heroicon-m-check-circle' : 'heroicon-m-flag')
                ->color($percentualeObiettivo >= 100 ? 'success' : ($percentualeObiettivo >= 75 ? 'warning' : 'danger')),
        ];
    }
}