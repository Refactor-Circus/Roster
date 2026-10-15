<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Statuses;

/**
 * @extends Factory<ProfileModel>
 */
final class ProfileFactory extends Factory
{
    protected $model = ProfileModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'display_name' => $this->faker->name(),
            'timezone' => 'UTC',
            'locale' => 'en',
            'status' => Statuses::users()::active(),
        ];
    }

    public function suspended(?string $reason = null): self
    {
        return $this->state([
            'status' => Statuses::users()::suspended(),
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }

    public function deactivated(?string $reason = null): self
    {
        return $this->state([
            'status' => Statuses::users()::deactivated(),
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }
}
