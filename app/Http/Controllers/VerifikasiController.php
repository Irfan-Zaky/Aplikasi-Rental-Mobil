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
        return view('verifikasi', ['user' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'ktp'    => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->verification_status === 'terverifikasi') {
            return back()->with('status', 'Data dirimu sudah terverifikasi.');
        }

        foreach ([$user->ktp_path, $user->selfie_path] as $lama) {
            if ($lama) {
                Storage::disk('local')->delete($lama);
            }
        }

        $user->update([
            'ktp_path'            => $request->file('ktp')->store('verifikasi/ktp', 'local'),
            'selfie_path'         => $request->file('selfie')->store('verifikasi/selfie', 'local'),
            'verification_status' => 'menunggu',
            'rejection_note'      => null,
        ]);

        return redirect()->route('verifikasi.create')
            ->with('status', 'Dokumen terkirim, menunggu verifikasi admin.');
    }
}