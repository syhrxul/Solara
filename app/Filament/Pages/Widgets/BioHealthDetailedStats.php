<?php

namespace App\Filament\Pages\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class BioHealthDetailedStats extends BaseWidget
{
    public ?array $skinWarning = null;
    public ?array $sleepCorrelation = null;

    protected function getStats(): array
    {
        if (!$this->skinWarning || !$this->sleepCorrelation) {
            return [];
        }

        // Build tips HTML for Skin
        $skinTipsHtml = '';
        if (isset($this->skinWarning['tips']) && count($this->skinWarning['tips']) > 0) {
            $skinTipsHtml = '<div class="mt-4"><strong class="text-xs uppercase tracking-widest opacity-70">Tindakan Preventif</strong><ul class="mt-2 space-y-1">';
            foreach ($this->skinWarning['tips'] as $tip) {
                $skinTipsHtml .= '<li class="flex items-start gap-2 text-sm"><span class="opacity-70 mt-0.5">•</span> <span>' . $tip . '</span></li>';
            }
            $skinTipsHtml .= '</ul></div>';
        }
        
        $skinDesc = new HtmlString('<div class="whitespace-normal leading-relaxed mt-1">' . ($this->skinWarning['message'] ?? '') . $skinTipsHtml . '</div>');

        // Build tips HTML for Sleep
        $sleepTipsHtml = '';
        if (isset($this->sleepCorrelation['tips']) && count($this->sleepCorrelation['tips']) > 0) {
            $sleepTipsHtml = '<div class="mt-4"><strong class="text-xs uppercase tracking-widest opacity-70">Saran Produktivitas</strong><ul class="mt-2 space-y-1">';
            foreach ($this->sleepCorrelation['tips'] as $tip) {
                $sleepTipsHtml .= '<li class="flex items-start gap-2 text-sm"><span class="opacity-70 mt-0.5">•</span> <span>' . $tip . '</span></li>';
            }
            $sleepTipsHtml .= '</ul></div>';
        }

        $sleepDesc = new HtmlString('<div class="whitespace-normal leading-relaxed mt-1">' . ($this->sleepCorrelation['message'] ?? '') . $sleepTipsHtml . '</div>');


        return [
            Stat::make('Kondisi Lingkungan', $this->skinWarning['title'] ?? '-')
                ->description($skinDesc)
                ->color($this->skinWarning['color'] ?? 'primary')
                ->icon($this->skinWarning['icon'] ?? 'heroicon-o-face-smile'),
                
            Stat::make('Reservasi Energi', $this->sleepCorrelation['title'] ?? '-')
                ->description($sleepDesc)
                ->color($this->sleepCorrelation['color'] ?? 'primary')
                ->icon($this->sleepCorrelation['icon'] ?? 'heroicon-o-moon'),
        ];
    }
}
