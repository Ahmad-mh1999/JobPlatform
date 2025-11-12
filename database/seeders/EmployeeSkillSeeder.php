<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class EmployeeSkillSeeder extends Seeder
{
    public function run()
    {
        $employeeProfiles = EmployeeProfile::all();
        $skills = Skill::all();

        foreach ($employeeProfiles as $profile) {
            // Attach random skills to each employee profile
            $randomSkills = $skills->random(rand(2, 5));
            foreach ($randomSkills as $skill) {
                $profile->skills()->attach($skill->id, [
                    'level' => collect(['beginner', 'intermediate', 'advanced', 'expert'])->random()
                ]);
            }
        }
    }
}
