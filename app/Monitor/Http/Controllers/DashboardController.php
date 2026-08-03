<?php

namespace App\Monitor\Http\Controllers;

class DashboardController
{
    public function __invoke(): \Illuminate\View\View
    {
        return view('monitor.dashboard', [
            'project' => config('monitor.project_name'),
            'apiBase' => url('/monitor/api'),
        ]);
    }
}
