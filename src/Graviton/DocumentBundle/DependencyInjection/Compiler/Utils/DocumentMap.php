<?php
/**
 * DocumentMap class file
 */

namespace Graviton\DocumentBundle\DependencyInjection\Compiler\Utils;

use Symfony\Component\Finder\Finder;

/**
 * Document map
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class DocumentMap
{
    /**
     * @var array
     */
    private $mappings = [];
    /**
     * @var Document[]
     */
    private $documents = [];

    /**
     * Constructor
     *
     * @param Finder $doctrineFinder   Doctrine mapping finder
     * @param Finder $serializerFinder Serializer mapping finder
     * @param Finder $validationFinder Validation mapping finder
     */
    public function __construct(Finder $doctrineFinder, Finder $serializerFinder, Finder $validationFinder)
    {
        $doctrineMap = $this->loadDoctrineClassMap($doctrineFinder);
        $serializerMap = $this->loadSerializerClassMap($serializerFinder);
        $validationMap = $this->loadValidationClassMap($validationFinder);

        foreach ($doctrineMap as $className => $doctrineMapping) {
            $this->mappings[$className] = [
                'doctrine'   => $doctrineMap[$className],
                'serializer' => isset($serializerMap[$className]) ? $serializerMap[$className] : null,
                'validation' => isset($validationMap[$className]) ? $validationMap[$className] : null,
            ];
        }
    }

    /**
     * Get document
     *
     * @param string $className Document class
     * @return Document
     */
    public function getDocument($className)
    {
        if (isset($this->documents[$className])) {
            return $this->documents[$className];
        }
        if (!isset($this->mappings[$className])) {
            throw new \InvalidArgumentException(sprintf('No XML mapping found for document "%s"', $className));
        }

        return $this->documents[$className] = $this->processDocument(
            $className,
            $this->mappings[$className]['doctrine'],
            $this->mappings[$className]['serializer'],
            $this->mappings[$className]['validation']
        );
    }

    /**
     * Get all documents
     *
     * @return Document[]
     */
    public function getDocuments()
    {
        return array_map([$this, 'getDocument'], array_keys($this->mappings));
    }

    /**
     * Process document
     *
     * @param string      $className         Class name
     * @param \DOMElement $doctrineMapping   Doctrine XML mapping
     * @param \DOMElement $serializerMapping Serializer XML mapping
     * @param \DOMElement $validationMapping Validation XML mapping
     * @return Document
     */
    private function processDocument(
        $className,
        \DOMElement $doctrineMapping,
        \DOMElement $serializerMapping = null,
        \DOMElement $validationMapping = null
    ) {
        if ($serializerMapping === null) {
            $serializerFields = [];
        } else {
            $serializerFields = array_reduce(
                $this->getSerializerFields($serializerMapping),
                function (array $fields, array $field) {
                    $fields[$field['fieldName']] = $field;
                    return $fields;
                },
                []
            );
        }

        if ($validationMapping === null) {
            $validationFields = [];
        } else {
            $validationFields = array_reduce(
                $this->getValidationFields($validationMapping),
                function (array $fields, array $field) {
                    $fields[$field['fieldName']] = $field;
                    return $fields;
                },
                []
            );
        }

        $fields = [];
        foreach ($this->getDoctrineFields($doctrineMapping) as $doctrineField) {
            $serializerField = isset($serializerFields[$doctrineField['name']]) ?
                $serializerFields[$doctrineField['name']] :
                null;
            $validationField = isset($validationFields[$doctrineField['name']]) ?
                $validationFields[$doctrineField['name']] :
                null;

            if ($doctrineField['type'] === 'collection') {
                $fields[] = new ArrayField(
                    $serializerField === null ? 'array<string>' : $serializerField['fieldType'],
                    $doctrineField['name'],
                    $serializerField === null ? $doctrineField['name'] : $serializerField['exposedName'],
                    $serializerField === null ? false : $serializerField['readOnly'],
                    $validationField === null ? false : $validationField['required'],
                    $serializerField === null ? false : $serializerField['searchable']
                );
            } else {
                $fields[] = new Field(
                    $doctrineField['type'],
                    $doctrineField['name'],
                    $serializerField === null ? $doctrineField['name'] : $serializerField['exposedName'],
                    $serializerField === null ? false : $serializerField['readOnly'],
                    $validationField === null ? false : $validationField['required'],
                    $serializerField === null ? false : $serializerField['searchable']
                );
            }
        }
        foreach ($this->getDoctrineEmbedOneFields($doctrineMapping) as $doctrineField) {
            $serializerField = isset($serializerFields[$doctrineField['name']]) ?
                $serializerFields[$doctrineField['name']] :
                null;
            $validationField = isset($validationFields[$doctrineField['name']]) ?
                $validationFields[$doctrineField['name']] :
                null;

            $fields[] = new EmbedOne(
                $this->getDocument($doctrineField['type']),
                $doctrineField['name'],
                $serializerField === null ? $doctrineField['name'] : $serializerField['exposedName'],
                $serializerField === null ? false : $serializerField['readOnly'],
                $validationField === null ? false : $validationField['required'],
                $serializerField === null ? false : $serializerField['searchable']
            );
        }
        foreach ($this->getDoctrineEmbedManyFields($doctrineMapping) as $doctrineField) {
            $serializerField = isset($serializerFields[$doctrineField['name']]) ?
                $serializerFields[$doctrineField['name']] :
                null;
            $validationField = isset($validationFields[$doctrineField['name']]) ?
                $validationFields[$doctrineField['name']] :
                null;

            $fields[] = new EmbedMany(
                $this->getDocument($doctrineField['type']),
                $doctrineField['name'],
                $serializerField === null ? $doctrineField['name'] : $serializerField['exposedName'],
                $serializerField === null ? false : $serializerField['readOnly'],
                $validationField === null ? false : $validationField['required']
            );
        }

        return new Document($className, $fields);
    }

    /**
     * Load doctrine class map
     *
     * @param Finder $finder Mapping finder
     * @return array
     */
    private function loadDoctrineClassMap(Finder $finder)
    {
        $classMap = [];
        foreach ($finder as $file) {
            $document = new \DOMDocument();
            $document->load($file);

            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('doctrine', 'http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping');

            $classMap = array_reduce(
                iterator_to_array($xpath->query('//*[self::doctrine:document or self::doctrine:embedded-document]')),
                function (array $classMap, \DOMElement $element) {
                    $classMap[$element->getAttribute('name')] = $element;
                    return $classMap;
                },
                $classMap
            );
        }

        return $classMap;
    }

    /**
     * Load serializer class map
     *
     * @param Finder $finder Mapping finder
     * @return array
     */
    private function loadSerializerClassMap(Finder $finder)
    {
        $classMap = [];
        foreach ($finder as $file) {
            $document = new \DOMDocument();
            $document->load($file);

            $xpath = new \DOMXPath($document);

            $classMap = array_reduce(
                iterator_to_array($xpath->query('//class')),
                function (array $classMap, \DOMElement $element) {
                    $classMap[$element->getAttribute('name')] = $element;
                    return $classMap;
                },
                $classMap
            );
        }

        return $classMap;
    }

    /**
     * Load validation class map
     *
     * @param Finder $finder Mapping finder
     * @return array
     */
    private function loadValidationClassMap(Finder $finder)
    {
        $classMap = [];
        foreach ($finder as $file) {
            $document = new \DOMDocument();
            $document->load($file);

            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('constraint', 'http://symfony.com/schema/dic/constraint-mapping');

            $classMap = array_reduce(
                iterator_to_array($xpath->query('//constraint:class')),
                function (array $classMap, \DOMElement $element) {
                    $classMap[$element->getAttribute('name')] = $element;
                    return $classMap;
                },
                $classMap
            );
        }

        return $classMap;
    }

    /**
     * Get serializer fields
     *
     * @param \DOMElement $mapping Serializer XML mapping
     * @return array
     */
    private function getSerializerFields(\DOMElement $mapping)
    {
        $xpath = new \DOMXPath($mapping->ownerDocument);

        return array_map(
            function (\DOMElement $element) {
                return [
                    'fieldName'   => $element->getAttribute('name'),
                    'fieldType'   => $this->getSerializerFieldType($element),
                    'exposedName' => $element->getAttribute('serialized-name') ?: $element->getAttribute('name'),
                    'readOnly'    => $element->getAttribute('read-only') === 'true',
                    'searchable'  => $element->getAttribute('searchable') === 'true',
                ];
            },
            iterator_to_array($xpath->query('property', $mapping))
        );
    }

    /**
     * Get serializer field type
     *
     * @param \DOMElement $field Field node
     * @return string|null
     */
    private function getSerializerFieldType(\DOMElement $field)
    {
        if ($field->getAttribute('type')) {
            return $field->getAttribute('type');
        }

        $xpath = new \DOMXPath($field->ownerDocument);

        $type = $xpath->query('type', $field)->item(0);
        return $type === null ? null : $type->nodeValue;
    }

    /**
     * Get validation fields
     *
     * @param \DOMElement $mapping Validation XML mapping
     * @return array
     */
    private function getValidationFields(\DOMElement $mapping)
    {
        $xpath = new \DOMXPath($mapping->ownerDocument);
        $xpath->registerNamespace('constraint', 'http://symfony.com/schema/dic/constraint-mapping');

        return array_map(
            function (\DOMElement $element) use ($xpath) {
                $constraints = $xpath->query('constraint:constraint[@name="NotBlank" or @name="NotNull"]', $element);
                return [
                    'fieldName' => $element->getAttribute('name'),
                    'required'  => $constraints->length > 0,
                ];
            },
            iterator_to_array($xpath->query('constraint:property', $mapping))
        );
    }

    /**
     * Get doctrine document fields
     *
     * @param \DOMElement $mapping Doctrine XML mapping
     * @return array
     */
    private function getDoctrineFields(\DOMElement $mapping)
    {
        $xpath = new \DOMXPath($mapping->ownerDocument);
        $xpath->registerNamespace('doctrine', 'http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping');

        return array_map(
            function (\DOMElement $element) {
                return [
                    'name' => $element->getAttribute('fieldName'),
                    'type' => $element->getAttribute('type'),
                ];
            },
            iterator_to_array($xpath->query('doctrine:field', $mapping))
        );
    }

    /**
     * Get doctrine document embed-one fields
     *
     * @param \DOMElement $mapping Doctrine XML mapping
     * @return array
     */
    private function getDoctrineEmbedOneFields(\DOMElement $mapping)
    {
        $xpath = new \DOMXPath($mapping->ownerDocument);
        $xpath->registerNamespace('doctrine', 'http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping');

        return array_map(
            function (\DOMElement $element) {
                return [
                    'name' => $element->getAttribute('field'),
                    'type' => $element->getAttribute('target-document'),
                ];
            },
            iterator_to_array($xpath->query('*[self::doctrine:embed-one or self::doctrine:reference-one]', $mapping))
        );
    }

    /**
     * Get doctrine document embed-many fields
     *
     * @param \DOMElement $mapping Doctrine XML mapping
     * @return array
     */
    private function getDoctrineEmbedManyFields(\DOMElement $mapping)
    {
        $xpath = new \DOMXPath($mapping->ownerDocument);
        $xpath->registerNamespace('doctrine', 'http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping');

        return array_map(
            function (\DOMElement $element) {
                return [
                    'name' => $element->getAttribute('field'),
                    'type' => $element->getAttribute('target-document'),
                ];
            },
            iterator_to_array($xpath->query('*[self::doctrine:embed-many or self::doctrine:reference-many]', $mapping))
        );
    }
}
