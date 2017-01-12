<?php

namespace Xiidea\EasyGuiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Xiidea\EasyGuiBundle\Util\BundleUtil;
use Xiidea\EasyGuiBundle\Util\CommandExecutor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/** Thats a fallback for PHP < 5.4 */
if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 128);
}

/**
 * Class DefaultController
 * @package Xiidea\EasyGuiBundle\Controller
 *
 * @Route("/api")
 */
class ApiController extends Controller
{
    /**
     * Create bundle view.
     *
     * @Route("/bundles", name="get_bundles")
     */
    public function getBundlesAction()
    {
        return new JsonResponse(BundleUtil::getCustomBundleNameList($this, $this->container));
    }

    /**
     * Create bundle view.
     *
     * @Route("/create-entity", name="api_create_entity")
     * @param Request $request
     * @return JsonResponse
     */
    public function createEntityAction(Request $request)
    {
        $executor = new CommandExecutor($this->get('kernel'));

        $bundleName = $request->get('bundleName');

        $entityName = $executor->formatEntityName($request->get('entityName'));

        $fields = $request->get('fields');
        $maps = $request->get('maps');
        $cmd = 'xiidea:generate:entity';

        $cmd.= ' --entity="' . $bundleName . ':' . $entityName . '"';
        if (is_array($fields) && count($fields) && !empty($fields[0]))
        {
            $cmd .= ' --fields="';
            foreach ($fields as $field)
            {
                $fieldName = $executor->formatFieldName($field['fieldName']);
                if ($fieldName && $fieldName != 'id')
                {
                    $cmd .= $fieldName . ':' . $field['type'] . "($field[parameter]) ";
                }
            }
            $cmd = rtrim($cmd, ' ');
            $cmd .= '"';
        }

        $cmd.= ' --entity="' . $bundleName . ':' . $entityName . '"';
        if (is_array($maps) && count($maps) && !empty($maps[0]))
        {
            $cmd .= ' --maps="';
            foreach ($maps as $map)
            {
                $fieldName = $executor->formatFieldName($map['fieldName']);
                if ($fieldName && $fieldName != 'id')
                {
                    $cmd .= $fieldName . ':' . addslashes($map['targetEntity']) . "(type=$map[type]) ";
                }
            }
            $cmd = rtrim($cmd, ' ');
            $cmd .= '"';
        }

        $cmd.= ' --format="annotation"';
        $cmd.= ' --no-interaction';


        $ret = $executor->execute($cmd);

        if($ret['errorcode'] != 0) {
            return $this->createErrorOutPut($ret['output']);
        }

        if($ret['errorcode'] == 0 && $request->get('schema')) {
            $ret = $executor->execute('doctrine:database:create');
            $ret = $executor->execute('doctrine:schema:update --force');
        }

        if($ret['errorcode'] != 0) {
            return $this->createErrorOutPut($ret['output']);
        }

        if($ret['errorcode'] == 0 && $request->get('crud')) {
            $ret = $executor->execute($this->generateCrudCmd($bundleName, $entityName));
        }

        if($ret['errorcode'] != 0) {
            return $this->createErrorOutPut($ret['output']);
        }

        return new JsonResponse(array('status' => 200, 'output' => 'Ok'));
    }

    private function generateCrudCmd($bundleName, $entityName) {
        $cmd = 'doctrine:generate:crud';
        $cmd.= ' --entity="' . $bundleName . ':' . $entityName . '"';
        $cmd.= ' --route-prefix="' . strtolower($entityName) . '"';
        $cmd.= ' --with-write';
        $cmd.= ' --no-interaction';

        return $cmd;
    }

    /**
     * @param $ret
     * @return JsonResponse
     */
    protected function createErrorOutPut($ret)
    {
        return new JsonResponse(array('output' => $ret, 'status'=>0), 500);
    }
}