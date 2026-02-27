<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        return [
            'project_id' => Project::factory(), 
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'priority' => $this->faker->randomElement(['normal','high','urgent']),
            'assignee_id' => User::factory(),
            'reviewer_id' => User::factory(),
        ];
    }
}
