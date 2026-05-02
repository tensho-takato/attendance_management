<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Models\Attendance;

class StampCorrectionRequestStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // ✅ 日付入力（年 + 月日）
            'work_year' => ['required', 'digits:4'],
            'work_md'   => ['required', 'regex:/^\d{1,2}\/\d{1,2}$/'],

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
            // 出勤・退勤（仕様メッセージ）
            'clock_in_at.required'     => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in_at.date_format'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.required'    => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.after'       => '出勤時間もしくは退勤時間が不適切な値です',

            // 備考
            'note.required'            => '備考を記入してください',

            // 日付（新仕様：文言は必要なら変えてOK）
            'work_year.required' => '日付が不正です',
            'work_year.digits'   => '日付が不正です',
            'work_md.required'   => '日付が不正です',
            'work_md.regex'      => '日付が不正です',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // まず attendance を確実に取る
            $attendance = $this->route('attendance'); // ルートモデルバインド
            if (! $attendance) {
                $validator->errors()->add('work_date', '日付が不正です');
                return;
            }

            // ✅ 年/月日がNGならここで終わり
            if ($validator->errors()->has('work_year') || $validator->errors()->has('work_md')) {
                return;
            }

            // ✅ 年 + 月日 → work_date(YYYY-MM-DD) を合成して request に入れる
            try {
                $year = (int) $this->input('work_year');
                [$m, $d] = array_map('intval', explode('/', $this->input('work_md')));

                if (!checkdate($m, $d, $year)) {
                    $validator->errors()->add('work_date', '日付が不正です');
                    return;
                }

                $workDate = sprintf('%04d-%02d-%02d', $year, $m, $d);
                $this->merge(['work_date' => $workDate]);
            } catch (\Throwable $e) {
                $validator->errors()->add('work_date', '日付が不正です');
                return;
            }

            // ✅ 変更先に既に勤怠があるならエラー（自分の同日別勤怠）
            $workDate = $this->input('work_date');

            $exists = Attendance::where('user_id', auth()->id())
                ->where('work_date', $workDate)
                ->where('id', '!=', $attendance->id) // 自分自身の勤怠は除外
                ->exists();

            if ($exists) {
                $validator->errors()->add('work_date', 'その日付の勤怠は既に存在します');
                return;
            }

            // clock_in/out がNGなら比較できないので終了
            if ($validator->errors()->has('clock_in_at') || $validator->errors()->has('clock_out_at')) {
                return;
            }

            $in  = $this->input('clock_in_at');
            $out = $this->input('clock_out_at');
            if (! $workDate || ! $in || ! $out) return;

            $workStart = Carbon::parse("$workDate $in");
            $workEnd   = Carbon::parse("$workDate $out");

            foreach ((array) $this->input('breaks', []) as $i => $b) {
                $bs = $b['break_start_at'] ?? null;
                $be = $b['break_end_at'] ?? null;

                if (! $bs && ! $be) continue;

                if (! $bs || ! $be) {
                    $validator->errors()->add("breaks.$i", '休憩時間が勤務時間外です');
                    continue;
                }

                $breakStart = Carbon::parse("$workDate $bs");
                $breakEnd   = Carbon::parse("$workDate $be");

                if ($breakEnd->lte($breakStart)) {
                    $validator->errors()->add("breaks.$i", '休憩時間が勤務時間外です');
                    continue;
                }

                if ($breakStart->lt($workStart) || $breakEnd->gt($workEnd)) {
                    $validator->errors()->add("breaks.$i", '休憩時間が勤務時間外です');
                    continue;
                }
            }
        });
    }
}