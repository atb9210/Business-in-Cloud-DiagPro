<?php

namespace App\Filament\Admin\Resources\RicorrenzeAttiveResource\Widgets;

use App\Models\RicorrenzeAttive;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RicorrenzeAttiveStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected function getStats(): array
    {
        // Totale ricorrenze attive
        $totaleAttive = RicorrenzeAttive::attive()->count();
        
        // Ricorrenze in scadenza (prossimi 30 giorni)
        $inScadenza = RicorrenzeAttive::attive()
            ->whereNotNull('data_fine')
            ->where('data_fine', '<=', now()->addDays(30))
            ->where('data_fine', '>=', now())
            ->count();
        
        // Ricavi mensili ricorrenti
        $ricaviMensili = RicorrenzeAttive::attive()->sum('prezzo');
        
        // Margine mensile (ricavi - costi)
        $costiMensili = RicorrenzeAttive::attive()->sum('costo');
        $margineMensile = $ricaviMensili - $costiMensili;
        
        // Calcolo ricavi e margini annuali basati sulla frequenza
        $ricaviAnnuali = DB::table('ordini_abbonamento')
            ->join('abbonamentos', 'ordini_abbonamento.abbonamento_id', '=', 'abbonamentos.id')
            ->where('ordini_abbonamento.attivo', true)
            ->where(function($query) {
                $query->whereNull('ordini_abbonamento.data_fine')
                      ->orWhere('ordini_abbonamento.data_fine', '>=', now());
            })
            ->selectRaw('SUM(CASE 
                WHEN abbonamentos.frequenza_rinnovo = "mensile" THEN ordini_abbonamento.prezzo * 12
                WHEN abbonamentos.frequenza_rinnovo = "trimestrale" THEN ordini_abbonamento.prezzo * 4
                WHEN abbonamentos.frequenza_rinnovo = "semestrale" THEN ordini_abbonamento.prezzo * 2
                WHEN abbonamentos.frequenza_rinnovo = "annuale" THEN ordini_abbonamento.prezzo
                ELSE ordini_abbonamento.prezzo * 12
            END) as ricavi_annuali')
            ->value('ricavi_annuali') ?? 0;
            
        $costiAnnuali = DB::table('ordini_abbonamento')
            ->join('abbonamentos', 'ordini_abbonamento.abbonamento_id', '=', 'abbonamentos.id')
            ->where('ordini_abbonamento.attivo', true)
            ->where(function($query) {
                $query->whereNull('ordini_abbonamento.data_fine')
                      ->orWhere('ordini_abbonamento.data_fine', '>=', now());
            })
            ->selectRaw('SUM(CASE 
                WHEN abbonamentos.frequenza_rinnovo = "mensile" THEN ordini_abbonamento.costo * 12
                WHEN abbonamentos.frequenza_rinnovo = "trimestrale" THEN ordini_abbonamento.costo * 4
                WHEN abbonamentos.frequenza_rinnovo = "semestrale" THEN ordini_abbonamento.costo * 2
                WHEN abbonamentos.frequenza_rinnovo = "annuale" THEN ordini_abbonamento.costo
                ELSE ordini_abbonamento.costo * 12
            END) as costi_annuali')
            ->value('costi_annuali') ?? 0;
            
        $margineAnnuale = $ricaviAnnuali - $costiAnnuali;
        
        // Nuove attivazioni questo mese
        $nuoveAttivazioni = RicorrenzeAttive::attive()
            ->whereMonth('data_inizio', now()->month)
            ->whereYear('data_inizio', now()->year)
            ->count();
        
        // Attivazioni mese precedente per calcolare la crescita
        $mesePrecedente = now()->copy()->subMonth();
        $attivazioneMesePrecedente = RicorrenzeAttive::attive()
            ->whereMonth('data_inizio', $mesePrecedente->month)
            ->whereYear('data_inizio', $mesePrecedente->year)
            ->count();
        
        $crescitaPercentuale = $attivazioneMesePrecedente > 0 
            ? (($nuoveAttivazioni - $attivazioneMesePrecedente) / $attivazioneMesePrecedente) * 100
            : 0;
        
        // Calcolo margine percentuale
        $marginePercentuale = $ricaviAnnuali > 0 ? (($margineAnnuale / $ricaviAnnuali) * 100) : 0;
        
        return [
            Stat::make('Ricorrenze Attive', $totaleAttive)
                ->description('Abbonamenti attualmente attivi')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('In Scadenza', $inScadenza)
                ->description('Prossimi 30 giorni')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($inScadenza > 0 ? 'warning' : 'success'),
                
            Stat::make('Ricavi Annuali', '€' . number_format($ricaviAnnuali, 2, ',', '.'))
                ->description('Fatturato ricorrente annuale')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
                
            Stat::make('Margine Annuale', '€' . number_format($margineAnnuale, 2, ',', '.'))
                ->description('Profitto ricorrente annuale')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($margineAnnuale > 0 ? 'success' : 'danger'),
                
            Stat::make('Margine %', number_format($marginePercentuale, 1) . '%')
                ->description('Percentuale di profitto')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($marginePercentuale > 0 ? 'success' : 'danger'),
                
            Stat::make('Nuove Attivazioni', $nuoveAttivazioni)
                ->description(sprintf(
                    '%s%s%% vs mese precedente',
                    $crescitaPercentuale >= 0 ? '+' : '',
                    number_format($crescitaPercentuale, 1)
                ))
                ->descriptionIcon($crescitaPercentuale >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($crescitaPercentuale >= 0 ? 'success' : 'danger'),
        ];
    }
}