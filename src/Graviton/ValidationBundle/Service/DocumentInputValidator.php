<?php

namespace Graviton\ValidationBundle\Service;


use Graviton\RestBundle\Service\RestUtils;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Webuni\FrontMatter\FrontMatter;

class DocumentInputValidator {

    /**
     * @var Router
     */
    private $router;

    /**
     * @var RestUtils
     */
    private $restUtils;

    /**
     * @var FrontMatter
     */
    private $frontMatter;

    public function __construct(Router $router, RestUtils $restUtils, FrontMatter $frontMatter)
    {
        $this->router = $router;
        $this->restUtils = $restUtils;
        $this->frontMatter = $frontMatter;
    }

    public function validate($dataType, $filename, $content)
    {
        $document = $this->frontMatter->parse($content);
        var_dump($document->getContent());

        var_dump($content);
    }


}
