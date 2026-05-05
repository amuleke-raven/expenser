<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    private static int $index = 0;

    private static array $names = ['Engineering', 'Finance', 'HR', 'Operations', 'Marketing', 'Sales', 'Legal', 'Product'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => self::$names[self::$index++ % count(self::$names)],
        ];
    }
}
