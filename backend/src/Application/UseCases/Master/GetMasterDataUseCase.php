<?php

declare(strict_types=1);

namespace App\Application\UseCases\Master;

use App\Domain\Repositories\MasterRepositoryInterface;

class GetMasterDataUseCase
{
    public function __construct(
        private readonly MasterRepositoryInterface $masterRepository,
    ) {}

    public function getDepartments(): array
    {
        return array_map(
            fn($item) => $item->toArray(),
            $this->masterRepository->getDepartments()
        );
    }

    public function getCourses(): array
    {
        return array_map(
            fn($item) => $item->toArray(),
            $this->masterRepository->getCourses()
        );
    }

    public function getClasses(): array
    {
        return array_map(
            fn($item) => $item->toArray(),
            $this->masterRepository->getClasses()
        );
    }

    public function getSemesters(): array
    {
        return array_map(
            fn($item) => $item->toArray(),
            $this->masterRepository->getSemesters()
        );
    }
}
