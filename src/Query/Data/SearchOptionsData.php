<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class SearchOptionsData extends Data
{
    /**
     * @param array|null $collections
     * @param string|null $conditionOptimization
     * @param string|null $countApproximate
     */
    public function __construct(
        /*
         * @var string[]|null
         */
        public ?array $collections,
        #[In(['auto', 'none'])]
        public ?string $conditionOptimization,
        #[In(['exact', 'cost'])]
        public ?string $countApproximate
    ) {
    }
}
