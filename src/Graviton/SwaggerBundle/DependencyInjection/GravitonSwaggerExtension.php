<?php

namespace Graviton\SwaggerBundle\DependencyInjection;

use Graviton\BundleBundle\DependencyInjection\GravitonBundleExtension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://scm.to/004w}
 *
 * @category GravitonSwaggerBundle
 * @package  Graviton
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class GravitonSwaggerExtension extends GravitonBundleExtension
{
    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getConfigDir()
    {
        return __DIR__.'/../Resources/config';
    }
}