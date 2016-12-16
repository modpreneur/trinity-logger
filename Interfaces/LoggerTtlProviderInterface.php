<?php

namespace Trinity\Bundle\LoggerBundle\Interfaces;

/**
 * Interface LoggerTtlProvider.
 *
 * This interface replaces dependency on trinity/settings
 */
interface LoggerTtlProviderInterface
{
    /**
     * Get ttl in days for the given type.
     *
     * @param string $typeName Name of the elasticLog type
     *
     * @return int Ttl in days. 0(zero) stands for no ttl.
     */
    public function getTtlForType(string $typeName);
}
