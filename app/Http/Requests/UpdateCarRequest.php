<?php

namespace App\Http\Requests;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
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
        /** @var Car|null $mobil */
        $mobil = $this->route('mobil');

        return [
            'merk' => ['required', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(['MPV', 'SUV', 'Hatchback', 'Sedan'])],
            'status' => ['required', Rule::in(array_column(CarStatus::cases(), 'value'))],
            'harga' => ['required', 'integer', 'min:0'],
            'plat' => [
                'required',
                'string',
                'max:255',
                Rule::unique('car', 'nomor_polisi')->ignore($mobil?->car_id, 'car_id'),
            ],
            'spesifikasi' => ['required', 'string'],
            'fotos' => ['sometimes', 'array', 'max:8'],
            'fotos.*' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
            'foto_utama' => ['nullable', 'integer', 'min:0'],
            'foto_lama' => ['sometimes', 'array', 'max:8'],
            'foto_lama.*' => [
                'nullable',
                'integer',
                Rule::exists('car_photo', 'photo_id')->where('car_id', $mobil?->car_id),
            ],
            'foto_utama_lama' => [
                'nullable',
                'integer',
                Rule::exists('car_photo', 'photo_id')->where('car_id', $mobil?->car_id),
            ],
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
