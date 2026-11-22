<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function create(Request $request): View
    {
        return view('customer.data-diri', ['user' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nomor_hp'     => ['required', 'string', 'max:20'],
            'nik'          => ['required', 'digits:16'],
            'foto_ktp'     => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'swafoto'      => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->verification_status === 'terverifikasi') {
            return redirect()->route('customer.pilihan-layanan');
        }

        foreach ([$user->ktp_path, $user->selfie_path] as $lama) {
            if ($lama) {
                Storage::disk('local')->delete($lama);
            }
        }

        $user->update([
            'nik'                 => $request->nik,
            'no_hp'               => $request->nomor_hp,
            'ktp_path'            => $request->file('foto_ktp')->store('verifikasi/ktp', 'local'),
            'selfie_path'         => $request->file('swafoto')->store('verifikasi/selfie', 'local'),
            'verification_status' => 'menunggu',
            'rejection_note'      => null,
        ]);

        return redirect()->route('customer.pilihan-layanan')
            ->with('status', 'Data diri terkirim, menunggu verifikasi admin.');
    }
}