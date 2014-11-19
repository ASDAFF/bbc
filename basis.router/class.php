<?php
/**
 * Basis components
 *
 * @package components
 * @subpackage basis
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 * @copyright Copyright (c) 2014, Nik Samokhvalov
 */
namespace Components\Basis;

use Bitrix\Main;


\CBitrixComponent::includeComponentClass(basename(dirname(__DIR__)).':basis');


/**
 * Abstraction basis router component
 */
abstract class BasisRouter extends \CBitrixComponent
{
    use Common;

    /**
     * @var array Paths of templates default
     */
    protected $defaultUrlTemplates404;

    /**
     * @var array Variables template paths
     */
    protected $componentVariables;

    /**
     * @var string Template page
     */
    protected $page;

    /**
     * @var string Template page default
     */
    protected $defaultPage = 'list';

    /**
     * @var string Template page default for SEF mode
     */
    protected $defaultSefPage = 'list';

    /**
     * Set default parameters for SEF URL's
     */
    protected function setSefDefaultParams()
    {
        $this->defaultUrlTemplates404 = array(
            'list' => '',
            'detail' => '#ELEMENT_ID#/'
        );

        $this->componentVariables = array('ELEMENT_ID');
    }

    /**
     * Is search request
     *
     * @return bool
     */
    protected function isSearchRequest()
    {
        if (strlen($_GET['q']) > 0 && $this->page !== 'detail')
        {
            return true;
        }

        return false;
    }

    /**
     * Set type of the page
     */
    protected function setPage()
    {
        $urlTemplates = array();

        if ($this->arParams['SEF_MODE'] === 'Y')
        {
            $variables = array();

            $urlTemplates = \CComponentEngine::MakeComponentUrlTemplates(
                $this->defaultUrlTemplates404,
                $this->arParams['SEF_URL_TEMPLATES']
            );

            $variableAliases = \CComponentEngine::MakeComponentVariableAliases(
                $this->defaultUrlTemplates404,
                $this->arParams['VARIABLE_ALIASES']
            );

            $this->page = \CComponentEngine::ParseComponentPath(
                $this->arParams['SEF_FOLDER'],
                $urlTemplates,
                $variables
            );

            if (!$this->page)
            {
                if ($this->arParams['SET_404'] === 'Y')
                {
                    $folder404 = str_replace('\\', '/', $this->arParams['SEF_FOLDER']);

                    if ($folder404 != '/')
                    {
                        $folder404 = '/'.trim($folder404, "/ \t\n\r\0\x0B")."/";
                    }

                    if (substr($folder404, -1) == '/')
                    {
                        $folder404 .= 'index.php';
                    }

                    if ($folder404 != Main\Context::getCurrent()->getRequest()->getRequestedPage())
                    {
                        $this->return404();
                    }
                }

                $this->page = $this->defaultSefPage;
            }

            if ($this->isSearchRequest() && $this->arParams['USE_SEARCH'] === 'Y')
            {
                $this->page = 'search';
            }

            \CComponentEngine::InitComponentVariables(
                $this->page,
                $this->componentVariables,
                $variableAliases,
                $variables
            );
        }
        else
        {
            $this->page = $this->defaultPage;
        }

        $this->arResult['FOLDER'] = $this->arParams['SEF_FOLDER'];
        $this->arResult['URL_TEMPLATES'] = $urlTemplates;
        $this->arResult['VARIABLES'] = $variables;
        $this->arResult['ALIASES'] = $variableAliases;
    }

    /**
     * Main logic basis router
     */
    final protected function executeBasis()
    {
        $this->includeModules();
        $this->checkParams();
        $this->startAjax();
        $this->executeProlog();

        $this->setSefDefaultParams();
        $this->setPage();
        $this->getResult();
        $this->returnDatas($this->page);

        $this->executeEpilog();
        $this->executeFinal();
        $this->stopAjax();
    }

    public function executeComponent()
    {
        try {
            $this->executeBasis();
        }
        catch (\Exception $e)
        {define('LOG_FILENAME', $_SERVER['DOCUMENT_ROOT'].'/log.txt');
        AddMessage2Log($e);
            $this->catchException($e);
        }
    }
}