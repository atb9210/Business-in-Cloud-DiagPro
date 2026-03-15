<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Cliente;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class ClientiChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?string $heading = 'Andamento Nuovi Clienti';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        // Ottieni i dati degli ultimi 6 mesi
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Cliente::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $data[] = $count;
            $labels[] = $month->format('M Y'); // Format: Jan 2023
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Nuovi Clienti',
                    'data' => $data,
                    'backgroundColor' => '#36A2EB',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
