<?php
/**
 * controller for gaufrette based file store
 */

namespace Graviton\ValidationBundle\Controller;

use Graviton\FileBundle\FileManager;
use Graviton\RestBundle\Controller\RestController;
use Graviton\RestBundle\Service\RestUtilsInterface;
use Graviton\SchemaBundle\SchemaUtils;
use Graviton\ValidationBundle\Service\DocumentInputValidator;
use GravitonDyn\FileBundle\Document\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormFactory;
use Graviton\DocumentBundle\Form\Type\DocumentType;
use Symfony\Component\Yaml\Parser;
use Webuni\FrontMatter\FrontMatter;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class ValidationController extends RestController
{
    /**
     * @var DocumentInputValidator
     */
    private $documentValidator;

    /**
     * @param Response           $response    Response
     * @param RestUtilsInterface $restUtils   Rest utils
     * @param Router             $router      Router
     * @param ValidatorInterface $validator   Validator
     * @param EngineInterface    $templating  Templating
     * @param FormFactory        $formFactory form factory
     * @param DocumentType       $formType    generic form
     * @param ContainerInterface $container   Container
     * @param SchemaUtils        $schemaUtils schema utils
     * @param FileManager        $fileManager Handles file specific tasks
     */
    public function __construct(
        Response $response,
        RestUtilsInterface $restUtils,
        Router $router,
        ValidatorInterface $validator,
        EngineInterface $templating,
        FormFactory $formFactory,
        DocumentType $formType,
        ContainerInterface $container,
        SchemaUtils $schemaUtils,
        DocumentInputValidator $documentValidator
    ) {
        parent::__construct(
            $response,
            $restUtils,
            $router,
            $validator,
            $templating,
            $formFactory,
            $formType,
            $container,
            $schemaUtils
        );

        $this->documentValidator = $documentValidator;
    }

    /**
     * Writes a new Entry to the database
     *
     * @param Request $request Current http request
     *
     * @return Response $response Result of action with data (if successful)
     */
    public function postAction(Request $request)
    {
        $record = $this->formValidator->checkForm(
            $this->formValidator->getForm($request, $this->getModel()),
            $this->getModel(),
            $this->formDataMapper,
            $request->getContent()
        );


        $frontMatter = new FrontMatter();
        $document = $frontMatter->parse($record->getContent());

        $frontMatterData = $document->getData();
        $target = $frontMatterData['target'];

        $reqContent = $document->getContent();
        $yaml = new Parser();
        $obj = $yaml->parse($reqContent);
        $json = json_encode($obj);

        $this->getRouter()->getContext()->setMethod('PUT');
        $dude = $this->getRouter()->match($target);

        $route = $this->getRouter()->getRouteCollection()->get($dude['_route']);
        $model = $this->getRestUtils()->getModelFromRoute($route);

        $thisRequest = new Request([], [], [], [], [], [], $json);
        $thisRequest->setMethod('PUT');

        $putRecord = $this->formValidator->checkForm(
            $this->formValidator->getForm($thisRequest, $model),
            $model,
            $this->formDataMapper,
            $thisRequest->getContent()
        );


        var_dump($putRecord);




        die;

        //$this->getRestUtils()->getModelFromRoute()

        $response = $this->getResponse();

        var_dump($this->documentValidator->validate($record->getType(), $record->getFilename(), $record->getContent()));
        die;
        return $response;
    }

    /**
     * respond with document if non json mime-type is requested
     *
     * @param Request $request Current http request
     * @param string  $id      id of file
     *
     * @return Response
     */
    public function getAction(Request $request, $id)
    {
        //return $this->notAllowed();
    }

    /**
     * Returns all records
     *
     * @param Request $request Current http request
     *
     * @return \Symfony\Component\HttpFoundation\Response $response Response with result or error
     */
    public function allAction(Request $request)
    {
        //return $this->notAllowed();
    }

    private function notAllowed()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        return $response;
    }

    /**
     * Update a record
     *
     * @param Number  $id      ID of record
     * @param Request $request Current http request
     *
     * @return Response $response Result of action with data (if successful)
     */
    public function putAction($id, Request $request)
    {
        //return $this->notAllowed();
    }

    /**
     * Deletes a record
     *
     * @param Number $id ID of record
     *
     * @return Response $response Result of the action
     */
    public function deleteAction($id)
    {
        //return $this->notAllowed();
    }

}
