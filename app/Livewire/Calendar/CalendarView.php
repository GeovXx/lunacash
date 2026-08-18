<?php

namespace App\Livewire\Calendar;

use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CalendarView extends Component
{
    public string $currentMonth;

    public string $currentYear;

    public function mount()
    {
        $this->currentMonth = Carbon::today()->format('m');
        $this->currentYear = Carbon::today()->format('Y');
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->format('m');
        $this->currentYear = $date->format('Y');
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->format('m');
        $this->currentYear = $date->format('Y');
    }

    public function goToToday()
    {
        $this->currentMonth = Carbon::today()->format('m');
        $this->currentYear = Carbon::today()->format('Y');
    }

    public function render(CalendarService $calendarService)
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Para o grid do calendário (desktop), precisamos pegar os dias do mês anterior
        // e do mês seguinte para completar a primeira e a última semana do grid.
        $startOfGrid = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfGrid = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        // Buscar todos os eventos do período (usando o CalendarService existente, isolado por user_id)
        $allEvents = $calendarService->getEventsForPeriod(Auth::id(), $startOfGrid->toDateString(), $endOfGrid->toDateString());

        // Agrupar os eventos por data
        $eventsByDate = collect($allEvents)->groupBy('date');

        // Construir os dias para o grid e a lista
        $days = [];
        $currentDate = $startOfGrid->copy();

        while ($currentDate->lte($endOfGrid)) {
            $dateString = $currentDate->toDateString();
            $events = $eventsByDate->get($dateString, collect())->sortBy(function ($event) {
                // Ordenar primeiro receitas, depois despesas, etc. Ou pelo source_type.
                return $event['direction'] === 'payable' ? 1 : 0;
            })->values()->all();

            $days[] = [
                'date' => $dateString,
                'day' => $currentDate->day,
                'isCurrentMonth' => $currentDate->month == $this->currentMonth,
                'isToday' => $currentDate->isToday(),
                'events' => $events,
            ];

            $currentDate->addDay();
        }

        return view('livewire.calendar.calendar-view', [
            'days' => $days,
            'monthName' => $date->translatedFormat('F'),
            'year' => $this->currentYear,
        ])->layout('layouts.app');
    }
}
