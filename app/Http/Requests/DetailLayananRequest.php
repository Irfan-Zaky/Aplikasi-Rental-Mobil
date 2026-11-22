<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetailLayananRequest extends FormRequest
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
        $denganSopir = $this->input('layanan') === 'dengan-sopir';

        return [
            'layanan' => ['required', Rule::in(['dengan-sopir', 'lepas-kunci'])],
            'alamat_penjemputan' => [$denganSopir ? 'required' : 'exclude', 'string', 'max:255'],
            'metode_penerimaan' => [$denganSopir ? 'exclude' : 'required', Rule::in(['diantar', 'ambil'])],
            'lokasi_pengantaran' => [
                $denganSopir || $this->input('metode_penerimaan') !== 'diantar' ? 'exclude' : 'required',
                'string',
                'max:255',
            ],
        ];
    }
}
