<?php

namespace Webkul\Insurance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HealthSherpaQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('user')->check();
    }

    public function rules(): array
    {
        return [
            'context' => 'required|array:product,exchange,coverage_family,coverage_type,plan_year',
            'context.product' => 'required|in:aca',
            'context.exchange' => 'required|in:on_exchange',
            'context.coverage_family' => 'required|in:medical',
            'context.coverage_type' => 'required|in:medical',
            'context.plan_year' => 'required|integer|min:2020|max:2099',
            'location' => 'required|array:zip_code,fips_code,state',
            'location.zip_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'location.fips_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'location.state' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],
            'household' => 'required|array:household_size,annual_income,effective_date,applicants',
            'household.household_size' => 'required|integer|min:1',
            'household.annual_income' => 'required|numeric|min:0',
            'household.effective_date' => 'sometimes|required|date_format:Y-m-d',
            'household.applicants' => 'required|array|min:1',
            'household.applicants.*' => 'required|array:member_id,age,relationship,uses_tobacco',
            'household.applicants.*.member_id' => 'required|string|max:100|distinct',
            'household.applicants.*.age' => 'required|integer|min:0',
            'household.applicants.*.relationship' => 'required|in:primary,spouse,dependent',
            'household.applicants.*.uses_tobacco' => 'required|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $applicants = collect($this->input('household.applicants'));

            if ($applicants->count() > (int) $this->input('household.household_size')
                || $applicants->where('relationship', 'primary')->count() !== 1) {
                $validator->errors()->add('household.applicants', trans('admin::app.healthsherpa.household-invalid'));
            }
        });
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $payload['context']['plan_year'] = (int) $payload['context']['plan_year'];
        $payload['household']['household_size'] = (int) $payload['household']['household_size'];
        $payload['household']['annual_income'] = (float) $payload['household']['annual_income'];

        foreach ($payload['household']['applicants'] as &$applicant) {
            $applicant['age'] = (int) $applicant['age'];
            $applicant['uses_tobacco'] = (bool) $applicant['uses_tobacco'];
        }

        return $payload;
    }
}
