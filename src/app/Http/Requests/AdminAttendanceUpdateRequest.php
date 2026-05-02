<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Models\Attendance;

class AdminAttendanceUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation()
    {
        $year = trim((string) $this->input('work_year', ''));
        $md   = trim((string) $this->input('work_md', ''));

        if ($year !== '' && $md !== '') {
            try {
                $d = Carbon::createFromFormat('Y-n/j', "{$year}-{$md}");
                $this->merge([
                    'work_date' => $d->toDateString(),
                ]);
            } catch (\Throwable $e) {
                $this->merge([
                    'work_date' => null,
                ]);
            }
        }
    }

    public function rules()
    {
        return [
            'work_year' => ['required', 'digits:4'],
            'work_md'   => ['required', 'regex:/^\d{1,2}\/\d{1,2}$/'],

            'work_date' => ['required', 'date'],

            'clock_in_at'  => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i', 'after:clock_in_at'],

            'note' => ['required', 'string', 'max:255'],

            'breaks' => ['array'],
            'breaks.*.break_start_at' => ['nullable', 'date_format:H:i'],
            'breaks.*.break_end_at'   => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_year.required' => '日付が不正です。',
            'work_year.digits'   => '日付が不正です。',
            'work_md.required'   => '日付が不正です。',
            'work_md.regex'      => '日付が不正です。',
            'work_date.required' => '日付が不正です。',
            'work_date.date'     => '日付が不正です。',

            'clock_in_at.required'     => '出勤時間もしくは退勤時間が不適切な値です。',
            'clock_in_at.date_format'  => '出勤時間もしくは退勤時間が不適切な値です。',
            'clock_out_at.required'    => '出勤時間もしくは退勤時間が不適切な値です。',
            'clock_out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です。',
            'clock_out_at.after'       => '出勤時間もしくは退勤時間が不適切な値です。',

            'note.required' => '備考を記入してください。',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (
                $validator->errors()->has('work_year')
                || $validator->errors()->has('work_md')
                || $validator->errors()->has('work_date')
                || $validator->errors()->has('clock_in_at')
                || $validator->errors()->has('clock_out_at')
            ) {
                return;
            }

            $attendanceId = $this->route('id');
            $attendance = Attendance::find($attendanceId);

            if (! $attendance) {
                return;
            }

            $date = $this->input('work_date');
            $in   = $this->input('clock_in_at');
            $out  = $this->input('clock_out_at');

            if (! $date || ! $in || ! $out) return;

            $existsOther = Attendance::where('user_id', $attendance->user_id)
                ->where('work_date', $date)
                ->where('id', '!=', $attendance->id)
                ->exists();

            if ($existsOther) {
                $validator->errors()->add('work_date', 'その日付の勤怠は既に登録されています。');
                return;
            }

            $workStart = Carbon::parse("$date $in");
            $workEnd   = Carbon::parse("$date $out");

            foreach ((array) $this->input('breaks', []) as $i => $b) {
                $bs = $b['break_start_at'] ?? null;
                $be = $b['break_end_at'] ?? null;

                if (! $bs && ! $be) continue;

                if (! $bs || ! $be) {
                    $validator->errors()->add("breaks.$i", '休憩時間が勤務時間外です。');
                    continue;
                }

                $breakStart = Carbon::parse("$date $bs");
                $breakEnd   = Carbon::parse("$date $be");

                if ($breakEnd->lte($breakStart)) {
                    $validator->errors()->add("breaks.$i", '休憩時間が勤務時間外です。');
                    continue;
                }

                if ($breakStart->lt($workStart) || $breakEnd->gt($workEnd)) {
                    $validator->errors()->add("breaks.$i", '休憩時間が勤務時間外です。');
                    continue;
                }
            }
        });
    }
}