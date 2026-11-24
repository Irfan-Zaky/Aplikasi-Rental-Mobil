<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DataDiriRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nomor_hp' => ['required', 'string', 'max:15'],
            'nik' => ['required', 'digits:16'],
            'foto_ktp' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'foto_sim' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'swafoto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
