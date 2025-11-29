<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) optional($this->user())->is_super;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_backup_enabled' => $this->boolean('full_backup_enabled'),
        ]);
    }

    public function rules(): array
    {
        $days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];

        return [
            'full_backup_enabled' => ['required','boolean'],
            'full_backup_frequency' => ['required', Rule::in(['daily','weekly','monthly'])],
            'full_backup_time' => ['required','date_format:H:i'],
            'full_backup_day_of_week' => [
                'nullable',
                Rule::in($days),
                Rule::requiredIf(fn () => $this->input('full_backup_frequency') === 'weekly'),
            ],
            'full_backup_day_of_month' => [
                'nullable',
                'integer','min:1','max:28',
                Rule::requiredIf(fn () => $this->input('full_backup_frequency') === 'monthly'),
            ],
        ];
    }
}
