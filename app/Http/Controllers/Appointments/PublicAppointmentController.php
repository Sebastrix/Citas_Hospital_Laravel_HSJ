<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class PublicAppointmentController extends Controller
{
    public function index(): View
    {
        return view('appointments.public.index');
    }

    public function waitingRoom(): View
    {
        return view('appointments.public.waiting-room');
    }
}
