<?php
/**
 * generate url from raw db data
 *
 * Here we get the raw structure that has been hydrated for $ref link cases
 * by doctrine and replace it with a route generated by the symfony router.
 * We do this in it's own listener due to the fact that there is no way that
 * we can inject anything useable into the default odm hydrator and it looks
 * rather futile to hack it so we can use our own custom hydration code.
 */

namespace Graviton\DocumentBundle\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class ExtReferenceListener
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var Request
     */
    private $request;

    /**
     * construct
     *
     * @param RouterInterface $router   symfony router
     * @param array           $mapping  map of collection_name => route_id
     * @param array           $fields   map of fields to process
     * @param RequestStack    $requests request
     */
    public function __construct(RouterInterface $router, array $mapping, array $fields, RequestStack $requests)
    {
        $this->router = $router;
        $this->mapping = $mapping;
        $this->fields = $fields;
        $this->request = $requests->getCurrentRequest();
    }

    /**
     * add a rel=self Link header to the response
     *
     * @param FilterResponseEvent $event response listener event
     *
     * @return void
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $content = trim($event->getResponse()->getContent());

        if (!$event->isMasterRequest() || empty($content)) {
            return;
        }

        $data = json_decode($event->getResponse()->getContent(), true);

        if (is_array($data) && !empty($data) && !is_string(array_keys($data)[0])) {
            foreach ($data as $index => $row) {
                $data[$index] = $this->mapItem($row);
            }
        } else {
            $data = $this->mapItem($data);
        }

        $event->getResponse()->setContent(json_encode($data));
    }

    /**
     * apply single mapping
     *
     * @param array $item item to apply mapping to
     *
     * @return array
     */
    private function mapItem(array $item)
    {
        if (!array_key_exists($this->request->attributes->get('_route'), $this->fields)) {
            return $item;
        }
        foreach ($this->fields[$this->request->attributes->get('_route')] as $field) {
            if (strpos($field, '.') !== false) {
                $topLevel = substr($field, 0, strpos($field, '.'));
                $subField = str_replace($topLevel . '.', '', $field);
                if (array_key_exists($topLevel, $item)) {
                    if (substr($subField, 0, 2) === '0.') {
                        $item[$topLevel] = $this->mapFields($item[$topLevel], $subField);
                    } else {
                        $item[$topLevel] = $this->mapField($item[$topLevel], $subField);
                    }
                }

            } elseif (is_array($item)) {
                $item = $this->mapField($item, $field);
            }
        }

        return $item;
    }

    /**
     * recursive mapper for embed-many fields
     *
     * @param array  $items items to map
     * @param string $field name of field to map
     *
     * @return array
     */
    private function mapFields($items, $field)
    {
        $field = substr($field, 2);
        foreach ($items as $key => $item) {
            $items[$key] = $this->mapField($item, $field);
        }

        return $items;
    }

    /**
     * recursive mapper for embed-one fields
     *
     * @param array  $item  item to map
     * @param string $field name of field to map
     *
     * @return array
     */
    private function mapField($item, $field)
    {
        if (array_key_exists($field, $item)) {
            $ref = json_decode($item[$field], true);
            $routeId = $this->mapping[$ref['$ref']];
            $item[$field] = $this->router->generate($routeId, ['id' => $ref['$id']], true);
        }

        return $item;
    }
}
