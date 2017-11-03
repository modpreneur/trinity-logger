<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class EntityMapper
 * @package Trinity\Bundle\LoggerBundle\Annotation
 *
 * @Annotation
 * @Target("CLASS")
 */
class EntityMapper
{
    /** @var array  */
    public $disabled = [];

    /**
     * @return array
     */
    public function getDisabled(): array
    {
        return $this->disabled;
    }
}
