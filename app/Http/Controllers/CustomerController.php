<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display the customer dashboard.
     */
    public function beranda(Request $request): View
    {
        $user = $request->user();

        return view('customer.beranda', [
            'namaCustomer' => $user->name,
            'fotoProfil' => $user->photo_profil,
            'jumlahAktif' => 0,
            'jumlahMenunggu' => 0,
            'jumlahSelesai' => 0,
            'pesananTerakhir' => null,
        ]);
    }

    /**
     * Display the customer's own rental history.
     */
    public function riwayat(Request $request): View
    {
        return view('customer.riwayat', [
            'riwayat' => collect(),
        ]);
    }

    /**
     * Display the customer's profile.
     */
    public function profil(Request $request): View
    {
        $user = $request->user();

        return view('customer.profil', [
            'namaCustomer' => $user->name,
            'emailCustomer' => $user->email,
            'nomorHpCustomer' => $user->no_hp,
            'fotoProfil' => $user->photo_profil,
        ]);
    }

    /**
     * Display the customer profile edit form.
     */
    public function editProfil(Request $request): View
    {
        $user = $request->user();

        return view('customer.edit-profil', [
            'namaCustomer' => $user->name,
            'emailCustomer' => $user->email,
            'nomorHpCustomer' => $user->no_hp,
            'fotoProfil' => $user->photo_profil,
        ]);
    }

    /**
     * Update the customer's profile information.
     */
    public function updateProfil(CustomerProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'no_hp' => $data['no_hp'],
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        if ($request->boolean('hapus_foto_profil')) {
            if ($user->photo_profil) {
                Storage::disk('public')->delete($user->photo_profil);
            }

            $user->photo_profil = null;
        } elseif ($request->hasFile('photo_profil')) {
            if ($user->photo_profil) {
                Storage::disk('public')->delete($user->photo_profil);
            }

            $user->photo_profil = $request->file('photo_profil')->store('profil', 'public');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('customer.profil')->with('status', 'profil-updated');
    }
}
