<?php

namespace App\Filament\Admin\Resources\CampagnaResource\Widgets;

use App\Models\Campagna;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CampagnaStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected function getStats(): array
    {
        // Calcoli per le metriche
        $campagneAttive = Campagna::where('stato', 'attiva')->count();
        $spesaTotale = Campagna::sum('spesa');
        $venditeTotali = Campagna::withCount('ordini')->get()->sum('ordini_count');
        $ricaviTotali = Campagna::with('ordini')->get()->sum(function ($campagna) {
            return $campagna->ordini->sum('prezzo_vendita');
        });
        
        // Calcolo ROI medio
        $roiMedio = 0;
        if ($spesaTotale > 0) {
            $roiMedio = (($ricaviTotali - $spesaTotale) / $spesaTotale) * 100;
        }
        
        // Calcolo CPA medio
        $cpaMedio = 0;
        if ($venditeTotali > 0) {
            $cpaMedio = $spesaTotale / $venditeTotali;
        }

        return [
            Stat::make('Campagne Attive', $campagneAttive)
                ->description('Campagne in corso')
                ->descriptionIcon('heroicon-m-play')
                ->color('success')
                ->chart([7, 12, 8, 15, 10, 18, $campagneAttive]),
                
            Stat::make('Spesa Totale', '€' . Number::format($spesaTotale, 2))
                ->description('Budget investito')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->chart([100, 150, 200, 180, 220, 250, $spesaTotale]),
                
            Stat::make('Vendite Generate', Number::format($venditeTotali))
                ->description('Ordini dalle campagne')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary')
                ->chart([5, 8, 12, 15, 18, 22, $venditeTotali]),
                
            Stat::make('ROI Medio', number_format($roiMedio, 1) . '%')
                ->description($roiMedio > 0 ? 'Ritorno positivo' : 'Ritorno negativo')
                ->descriptionIcon($roiMedio > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($roiMedio > 0 ? 'success' : 'danger')
                ->chart($roiMedio > 0 ? [10, 15, 20, 25, 30, 35, $roiMedio] : [-10, -5, 0, 5, 10, 15, $roiMedio]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}