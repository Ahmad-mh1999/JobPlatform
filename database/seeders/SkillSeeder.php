<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run()
    {
        $skills = [
            // Programming Languages
            ['name' => 'PHP', 'category' => 'programming'],
            ['name' => 'JavaScript', 'category' => 'programming'],
            ['name' => 'Python', 'category' => 'programming'],
            ['name' => 'Java', 'category' => 'programming'],
            ['name' => 'C#', 'category' => 'programming'],
            ['name' => 'C++', 'category' => 'programming'],
            ['name' => 'TypeScript', 'category' => 'programming'],
            ['name' => 'Go', 'category' => 'programming'],
            ['name' => 'Ruby', 'category' => 'programming'],
            ['name' => 'Swift', 'category' => 'programming'],

            // Frameworks
            ['name' => 'Laravel', 'category' => 'framework'],
            ['name' => 'React', 'category' => 'framework'],
            ['name' => 'Vue.js', 'category' => 'framework'],
            ['name' => 'Angular', 'category' => 'framework'],
            ['name' => 'Django', 'category' => 'framework'],
            ['name' => 'Spring Boot', 'category' => 'framework'],
            ['name' => 'Express.js', 'category' => 'framework'],
            ['name' => 'ASP.NET', 'category' => 'framework'],
            ['name' => 'Symfony', 'category' => 'framework'],
            ['name' => 'Ruby on Rails', 'category' => 'framework'],

            // Databases
            ['name' => 'MySQL', 'category' => 'database'],
            ['name' => 'PostgreSQL', 'category' => 'database'],
            ['name' => 'MongoDB', 'category' => 'database'],
            ['name' => 'Redis', 'category' => 'database'],
            ['name' => 'SQLite', 'category' => 'database'],
            ['name' => 'Oracle', 'category' => 'database'],
            ['name' => 'SQL Server', 'category' => 'database'],

            // Tools & Technologies
            ['name' => 'Git', 'category' => 'tool'],
            ['name' => 'Docker', 'category' => 'tool'],
            ['name' => 'Kubernetes', 'category' => 'tool'],
            ['name' => 'AWS', 'category' => 'tool'],
            ['name' => 'Azure', 'category' => 'tool'],
            ['name' => 'Linux', 'category' => 'tool'],
            ['name' => 'Node.js', 'category' => 'tool'],
            ['name' => 'REST API', 'category' => 'tool'],
            ['name' => 'GraphQL', 'category' => 'tool'],
            ['name' => 'Microservices', 'category' => 'tool'],

            // Soft Skills
            ['name' => 'Project Management', 'category' => 'soft_skill'],
            ['name' => 'Team Leadership', 'category' => 'soft_skill'],
            ['name' => 'Communication', 'category' => 'soft_skill'],
            ['name' => 'Problem Solving', 'category' => 'soft_skill'],
            ['name' => 'Agile Methodology', 'category' => 'soft_skill'],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
