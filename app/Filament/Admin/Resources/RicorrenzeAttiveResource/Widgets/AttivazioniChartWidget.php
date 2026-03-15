<?php

namespace App\Filament\Admin\Resources\RicorrenzeAttiveResource\Widgets;

use App\Models\RicorrenzeAttive;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AttivazioniChartWidget extends ChartWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?string $heading = 'Andamento Ultimi 12 Mesi';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $mesi = [];
        $attivazioni = [];
        $ricavi = [];
        $margini = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $data = now()->subMonths($i);
            $mesi[] = $data->format('M Y');
            
            // Conteggio attivazioni
            $count = RicorrenzeAttive::whereMonth('data_inizio', $data->month)
                ->whereYear('data_inizio', $data->year)
                ->count();
            $attivazioni[] = $count;
            
            // Calcolo ricavi del mese (normalizzati annualmente)
            $ricaviMese = DB::table('ordini_abbonamento')
                ->join('abbonamentos', 'ordini_abbonamento.abbonamento_id', '=', 'abbonamentos.id')
                ->whereMonth('ordini_abbonamento.data_inizio', $data->month)
                ->whereYear('ordini_abbonamento.data_inizio', $data->year)
                ->selectRaw('SUM(CASE 
                    WHEN abbonamentos.frequenza_rinnovo = "mensile" THEN ordini_abbonamento.prezzo * 12
                    WHEN abbonamentos.frequenza_rinnovo = "trimestrale" THEN ordini_abbonamento.prezzo * 4
                    WHEN abbonamentos.frequenza_rinnovo = "semestrale" THEN ordini_abbonamento.prezzo * 2
                    WHEN abbonamentos.frequenza_rinnovo = "annuale" THEN ordini_abbonamento.prezzo
                    ELSE ordini_abbonamento.prezzo * 12
                END) as ricavi_annuali')
                ->value('ricavi_annuali') ?? 0;
            $ricavi[] = round($ricaviMese, 2);
             
             // Calcolo margini del mese (normalizzati annualmente)
             $costiMese = DB::table('ordini_abbonamento')
                 ->join('abbonamentos', 'ordini_abbonamento.abbonamento_id', '=', 'abbonamentos.id')
                 ->whereMonth('ordini_abbonamento.data_inizio', $data->month)
                 ->whereYear('ordini_abbonamento.data_inizio', $data->year)
                 ->selectRaw('SUM(CASE 
                     WHEN abbonamentos.frequenza_rinnovo = "mensile" THEN ordini_abbonamento.costo * 12
                     WHEN abbonamentos.frequenza_rinnovo = "trimestrale" THEN ordini_abbonamento.costo * 4
                     WHEN abbonamentos.frequenza_rinnovo = "semestrale" THEN ordini_abbonamento.costo * 2
                     WHEN abbonamentos.frequenza_rinnovo = "annuale" THEN ordini_abbonamento.costo
                     ELSE ordini_abbonamento.costo * 12
                 END) as costi_annuali')
                 ->value('costi_annuali') ?? 0;
             $margini[] = round($ricaviMese - $costiMese, 2);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Attivazioni',
                    'data' => $attivazioni,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'yAxisID' => 'y',
                ],
                [
                     'label' => 'Ricavi (€)',
                     'data' => $ricavi,
                     'backgroundColor' => 'rgba(147, 51, 234, 0.7)',
                     'borderColor' => 'rgb(147, 51, 234)',
                     'yAxisID' => 'y1',
                 ],
                 [
                     'label' => 'Margini (€)',
                     'data' => $margini,
                     'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                     'borderColor' => 'rgb(34, 197, 94)',
                     'yAxisID' => 'y1',
                 ],
            ],
            'labels' => $mesi,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'onClick' => 'function(e, legendItem, legend) {
                        const index = legendItem.datasetIndex;
                        const chart = legend.chart;
                        const meta = chart.getDatasetMeta(index);
                        meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
                        chart.update();
                    }',
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Attivazioni',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                         'display' => true,
                         'text' => 'Importi (€)',
                     ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}