<?php
/**
 * trigger update of translation loader cache when translatables are updated
 */

namespace Graviton\I18nBundle\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Graviton\I18nBundle\Document\Translatable;

/**
 * trigger update of translation loader cache when translatables are updated
 *
 * @category I18nBundle
 * @package  Graviton
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class PostPersistTranslatableListener implements EventSubscriber
{
    /**
     * {@inheritDocs}
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return array('postPersist');
    }

    /**
     * post persist callback method
     *
     * @param LifecycleEventArgs $event event args
     *
     * @return void
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($object instanceof Translatable) {
            $domain = $object->getDomain();
            $locale = $object->getLocale();

            // faster less blocking operation than using touch()
            $triggerFile = __DIR__.'/../Resources/translations/'.$domain.'.'.$locale.'.odm';
            if (!file_exists($triggerFile)) {
                touch($triggerFile);
            }
            $fp = fopen($triggerFile, 'rw');
            ftruncate($fp, 0);
        }
    }
}
