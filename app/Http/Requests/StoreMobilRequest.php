<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMobilRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'merk' => ['required', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(['MPV', 'SUV', 'Hatchback', 'Sedan'])],
            'status' => ['required', Rule::in(['Tersedia', 'Disewa', 'Perawatan'])],
            'harga' => ['required', 'integer', 'min:0'],
            'plat' => ['required', 'string', 'max:255', Rule::unique('mobils', 'nomor_polisi')],
            'spesifikasi' => ['required', 'string'],
            'fotos' => ['required', 'array', 'min:1', 'max:8'],
            'fotos.*' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
            'foto_utama' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama mobil',
            'harga' => 'harga sewa',
            'plat' => 'nomor polisi',
            'fotos' => 'foto mobil',
            'fotos.*' => 'foto mobil',
        ];
    }
}
