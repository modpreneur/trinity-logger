<?php

namespace Trinity\Bundle\LoggerBundle\Services;

use Trinity\Bundle\LoggerBundle\Interfaces\LoggerTtlProviderInterface;

/**
 * Class DefaultTtlProvider
 */
class DefaultTtlProvider implements LoggerTtlProviderInterface
{
    /**
     * Get ttl in days for the given type.
     *
     * @param string $typeName Name of the elasticLog type
     *
     * @return int Ttl in days. 0(zero) stands for no ttl.
     */
    public function getTtlForType(string $typeName): int
    {
        return 0;
    }
}
