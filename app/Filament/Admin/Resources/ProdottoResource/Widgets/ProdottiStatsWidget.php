<?php

namespace App\Filament\Admin\Resources\ProdottoResource\Widgets;

use App\Models\Prodotto;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProdottiStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected function getStats(): array
    {
        // Calcola le metriche solo per prodotti fisici attivi (esclude servizi e prodotti non attivi)
        $prodotti = Prodotto::where('stato', 'attivo')->get();
        
        $quantitaTotale = $prodotti->where('tipo', 'fisico')->sum('quantita_disponibile');
        $valoreInventario = $prodotti->where('tipo', 'fisico')->sum(function ($prodotto) {
            return ($prodotto->quantita_disponibile ?? 0) * $prodotto->costo;
        });
        $marginePotenziale = $prodotti->where('tipo', 'fisico')->sum(function ($prodotto) {
            return ($prodotto->quantita_disponibile ?? 0) * ($prodotto->prezzo - $prodotto->costo);
        });
        
        $marginePercentuale = $valoreInventario > 0 
            ? ($marginePotenziale / ($valoreInventario + $marginePotenziale)) * 100 
            : 0;

        return [
            Stat::make('Quantità Totale', number_format($quantitaTotale, 0, ',', '.'))
                ->description('Prodotti fisici in magazzino')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
                
            Stat::make('Valore Inventario', '€ ' . number_format($valoreInventario, 2, ',', '.'))
                ->description('Valore totale a costo')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Margine Potenziale', '€ ' . number_format($marginePotenziale, 2, ',', '.'))
                ->description('Profitto se venduto tutto')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($marginePotenziale > 0 ? 'success' : 'danger'),
                
            Stat::make('Margine % Potenziale', number_format($marginePercentuale, 1) . '%')
                ->description('Percentuale di profitto')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($marginePercentuale > 30 ? 'success' : ($marginePercentuale > 15 ? 'warning' : 'danger')),
        ];
    }
}