<?php

namespace App\Services;

use App\Repositories\LegacySiteRepository;

final class LegacyRepository
{
    public function __construct(private readonly LegacySiteRepository $repository)
    {
    }

    public function instance(): LegacySiteRepository
    {
        return $this->repository;
    }
}
