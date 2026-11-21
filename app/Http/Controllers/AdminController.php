<?php

namespace App\Http\Controllers;

use App\Models\Mobil;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        return view('dashboard', [
            'title' => 'home',
            'totalMobil' => Mobil::count(),
            'mobilTersedia' => Mobil::where('status', 'Tersedia')->count(),
            'mobilDisewa' => Mobil::where('status', 'Disewa')->count(),
            'totalPesanan' => 0,
            'pesananMenunggu' => 0,
        ]);
    }
}
