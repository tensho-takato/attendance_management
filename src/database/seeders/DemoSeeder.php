<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class DemoSeeder extends Seeder
{
    public function run()
    {
        $password = Hash::make('password123');

        $admin = User::updateOrCreate(
            ['email' => 'admin@coachtech.com'],
            [
                'name' => '管理者',
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $staffs = [
            ['name' => '西 伶奈',   'email' => 'reina.n@coachtech.com'],
            ['name' => '山田 太郎', 'email' => 'taro.y@coachtech.com'],
            ['name' => '増田 一世', 'email' => 'issei.m@coachtech.com'],
            ['name' => '山本 敬吉', 'email' => 'keikichi.y@coachtech.com'],
            ['name' => '秋田 朋美', 'email' => 'tomomi.a@coachtech.com'],
            ['name' => '中西 敦夫', 'email' => 'norio.n@coachtech.com'],
        ];

        $users = [];
        foreach ($staffs as $s) {
            $users[] = User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => $password,
                    'role' => User::ROLE_USER,
                    'email_verified_at' => now(),
                ]
            );
        }

        $year = now()->year;

        foreach ([3, 4] as $month) {
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $end   = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();

            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                if ($day->isWeekend()) continue;

                foreach ($users as $i => $user) {
                    $seed = intval($day->format('Ymd')) + ($i * 13);
                    $offsetIn  = ($seed % 3) * 5;
                    $offsetOut = ($seed % 4) * 5;

                    $clockIn  = $day->copy()->setTime(9, 0)->addMinutes($offsetIn);
                    $clockOut = $day->copy()->setTime(18, 0)->addMinutes($offsetOut);

                    $attendance = Attendance::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'work_date' => $day->toDateString(),
                        ],
                        [
                            'clock_in_at' => $clockIn,
                            'clock_out_at' => $clockOut,
                            'note' => null,
                        ]
                    );

                    AttendanceBreak::where('attendance_id', $attendance->id)->delete();

                    AttendanceBreak::create([
                        'attendance_id' => $attendance->id,
                        'break_start_at' => $day->copy()->setTime(12, 0),
                        'break_end_at'   => $day->copy()->setTime(13, 0),
                    ]);

                    if ($day->isWednesday()) {
                        AttendanceBreak::create([
                            'attendance_id' => $attendance->id,
                            'break_start_at' => $day->copy()->setTime(15, 0),
                            'break_end_at'   => $day->copy()->setTime(15, 15),
                        ]);
                    }
                }
            }
        }
    }
}