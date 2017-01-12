<?php
/**
 * Symfony2 - Rapid Development Bundle.
 *
 * @package     Xiidea\EasyGuiBundle\Controller
 * @author      Robert Gies <mail@rgies.com>
 * @copyright   Copyright © 2014 by Robert Gies
 * @license     MIT
 * @date        2014-02-04
 */

namespace Xiidea\EasyGuiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Filesystem\Filesystem as FS;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use Xiidea\EasyGuiBundle\Util\CommandExecutor;
use Xiidea\EasyGuiBundle\Util\BundleUtil;
use lessc;

use Exception;
use RuntimeException;

/** Thats a fallback for PHP < 5.4 */
if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 128);
}

/**
 * Class DefaultController
 * @package Xiidea\EasyGuiBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * Show dashboard view.
     *
     * @Route("/", name="gui")
     * @Template()
     */
    public function indexAction()
    {
        $bundles = BundleUtil::getCustomBundleNameList($this, $this->container);
        if (count($bundles) == 0)
        {
            return $this->redirect('create-bundle');
        }

        return array();
    }

    /**
     * Create bundle view.
     *
     * @Route("/create-bundle", name="guiCreateBundle")
     * @Template()
     */
    public function createBundleAction()
    {
        return array('templates' => $this->_getBundleTemplateNames());
    }

    /**
     * Create crud view.
     *
     * @Route("/create-crud", name="guiCreateCrud")
     * @Template()
     */
    public function createCrudAction()
    {
        $dataTypes = array(
            'integer',
            'string(5)',
            'string(10)',
            'string(15)',
            'string(20)',
            'string(50)',
            'string(100)',
            'string(255)',
            'text',
            'boolean',
            'datetime',
            'date',
            'time',
            'float',
            'smallint',
            'bigint',
            'array',
        );

        return array(
            'types'   => $dataTypes,
            'bundles' => BundleUtil::getCustomBundleNameList($this, $this->container)
        );
    }

    /**
     * Install bundle view.
     *
     * @Route("/install-bundle", name="guiInstallBundle")
     * @Template()
     */
    public function installBundleAction()
    {
        $configFile = dirname(__DIR__). '/Resources/config/bundle_repository.xml';
        $bundles = simplexml_load_file($configFile);

        // Thats resolve a small problem with kernel entries
        foreach ($bundles->bundle as $key => $bundle) {

            $installed = BundleUtil::bundleInstalled($this, (string)$bundle->bundleName);
            $bundle->addChild('installed', ($installed === TRUE) ? '1' : '0');
            $bundle->bundleNamespace = urlencode($bundle->bundleNamespace);
            $bundle->routingEntry = json_encode($bundle->routingEntry);

            if (isset($bundle->installNotes))
            {
                $bundle->installNotes = nl2br(trim($bundle->installNotes));
            }

            // encode configuration for config.yml
            if (isset($bundle->configuration))
            {
                $bundle->configuration = json_encode($bundle->configuration);
            }
            else
            {
                $bundle->configuration = '{}';
            }

        }

        return array('bundles' => $bundles);
    }

    /**
     * Ajax action to install assets.
     *
     * @Route("/install-assets-ajax", name="guiInstallAssetsAjax")
     * @Template()
     */
    public function installAssetsAjaxAction()
    {
        // install assets
        $executor = new CommandExecutor($this->get('kernel'));
        $ret = $executor->execute('assets:install');

        // return json result
        echo json_encode($ret);
        exit;
    }

    /**
     * Ajax action to update schema.
     *
     * @Route("/update-schema-ajax", name="guiUpdateSchemaAjax")
     * @Template()
     */
    public function updateSchemaAjaxAction()
    {
        // update database schema
        $executor = new CommandExecutor($this->get('kernel'));
        $ret = $executor->execute('doctrine:schema:update --force');

        // return json result
        echo json_encode($ret);
        exit;
    }

    /**
     * Ajax action to install defined package.
     *
     * @Route("/install-bundle-ajax", name="guiInstallBundleAjax")
     * @Template()
     */
    public function installBundleAjaxAction()
    {

        $request = Request::createFromGlobals();
        $bundlePath = $request->request->get('bundlePath');
        $bundleVersion = $request->request->get('bundleVersion');
        $bundleName = $request->request->get('bundleName');
        $bundleTitle = $request->request->get('bundleTitle');
        $bundleNamespace = $request->get('bundleNamespace');
        $rootPath = rtrim(dirname($this->get('kernel')->getRootDir()), '/');
        $routingEntry = $request->request->get('routingEntry');
        $configuration = $request->request->get('configuration');

        if (!$bundlePath)
        {
            die('Error: Bundle path not set.');
        }

        if (!$bundleVersion)
        {
            die('Error: Bundle version not set.');
        }

        // insert bundle to composer.json file
        $composerJsonFile = $rootPath . '/composer.json';

        // use symfony file system
        $fs = new FS();
        if ($fs->exists($composerJsonFile))
        {
            // read entries from file
            $composerFile = file_get_contents($composerJsonFile);

            // decode json string into object
            $composerJson = json_decode($composerFile);

            // check object if it a valid object
            if ($composerJson && is_object($composerJson))
            {
                // retrieve all requirements from composer json object
                $composerRequires =  $composerJson->require;

                // check if we have allready set the new bundle
                if (!isset($composerRequires->{$bundlePath}))
                {
                    // set new bundle and their version
                    $composerRequires->{$bundlePath} = $bundleVersion;

                    // override composer requirements with new one
                    $composerJson->require = $composerRequires;

                    // encode the json object
                    $data = json_encode($composerJson, JSON_PRETTY_PRINT);

                    // prepare json to a pretty json
                    $data = BundleUtil::getPrettyJson($data);

                    // dump json string into file
                    // mode 0664 = read/write for user and group and read for all other
                    file_put_contents($composerJsonFile, $data);
                    //$fs->dumpFile($composerJsonFile, '', 0777);
                    //$fs->dumpFile($composerJsonFile, $data, 0777);
                }

                unset($composerRequires);
            }

            unset($composerJson);
        }

        unset($composerJsonFile, $fs);

        /**
         * Anonymous callback for process
         *
         * @param string    $type   The process type
         * @param mixed     $output The output of process
         */
        $callback = function($type, $output)
        {
            if (Process::ERR === $type)
            {
                echo 'error on executing composer: \n\n' . $output;
                die;
            }
        };

        // execute composer self-update
        $processBuilder = new ProcessBuilder($this->getComposerArguments($rootPath, 'self-update'));
        $processBuilder->setEnv('PATH', $request->server->get('PATH'));
        $processBuilder->setEnv('COMPOSER_HOME', $rootPath . '/bin');

        $process = $processBuilder->getProcess();
        $process->setTimeout(90);
        $process->setIdleTimeout(NULL);
        $ret = $process->run($callback);
        if ($ret == 0)
        {
            echo 'Execute composer self-update finished.';
        }

        unset($processBuilder, $process);

        // execute composer update on specified bundle
        $processBuilder = new ProcessBuilder($this->getComposerArguments($rootPath, 'update', $bundlePath));
        $processBuilder->setEnv('PATH', $request->server->get('PATH'));
        $processBuilder->setEnv('COMPOSER_HOME', $rootPath . '/bin');
        $processBuilder->setWorkingDirectory($rootPath);

        // Generate output for AJAX call
        echo 'Running update on: ' . $bundleTitle;

        $process = $processBuilder->getProcess();
        $process->setTimeout(3600);
        $process->setIdleTimeout(60);
        $ret = $process->run($callback);

        if ($ret == 0)
        {
            // Register new bundle after it was installed
            $kernel = $this->get('kernel');
            if ($kernel instanceof Kernel)
            {
                // Check if bundle already installed
                $bundleInstalled = BundleUtil::bundleInstalled($this, $bundleTitle);
                if (!$bundleInstalled)
                {
                    // Register bundle
                    $km = new KernelManipulator($kernel);
                    try
                    {
                        $km->addBundle(urldecode($bundleNamespace) . '\\' . $bundleName);
                    }
                    catch (RuntimeException $ex)
                    {
                        echo($ex->getMessage());
                        die;
                    }
                    unset($km);
                }
            }

            // Handle configuration at config.yml
            if (isset($configuration) && !empty($configuration))
            {
                $this->_addConfiguration($bundleTitle, $rootPath, $configuration);
            }

            // Handle route installation
            if (isset($routingEntry) && !empty($routingEntry))
            {
                $this->_addRouting($bundleTitle, $rootPath, $routingEntry);
            }

            // Clear cache
            BundleUtil::clearCache($kernel);

            // handle success
            echo 'Done';
        }
        else
        {
            // handle error
            echo 'Error on updating: ' . $bundleTitle . '\n\n' . Process::$exitCodes[$ret];
        }

        unset($processBuilder, $process);
        exit;
    }

    /**
     * Create controller view.
     *
     * @Route("/create-controller", name="guiCreateController")
     * @Template()
     */
    public function createControllerAction(Request $request)
    {
        $controller = '';
        $action = '';

        // try to get the action name from the given uri
        if ($request->query->has('uri'))
        {
            $uri = $request->query->get('uri');
            $parts = explode('/', $uri);
            if (count($parts) == 2)
            {
                $controller = 'Default';
                $action = $parts[1];
            }
            elseif (count($parts) > 2)
            {
                $controller = $parts[1];
                $action = $parts[2];
            }
        }

        return array(
            'bundles' => BundleUtil::getCustomBundleNameList($this, $this->container),
            'controller' => $controller,
            'action' => $action
        );
    }

    /**
     * Ajax Execute Command.
     *
     * @Route("/execute-command", name="guiExecuteCommand")
     * @Template()
     */
    public function execCommandAction()
    {
        $request = Request::createFromGlobals()->request;
        $executor = new CommandExecutor($this->get('kernel'));

        $command = $request->get('command');
        $rootPath = rtrim(dirname($this->get('kernel')->getRootDir()), '/');

        switch($command)
        {
            // generate new bundle
            case 'gui:generate:bundle':
                $bundleName = $executor->formatBundleName($request->get('bundleName'));
                $namespace = $executor->formatNamespace($request->get('bundleNamespace')) . '/' . $bundleName;
                $templateName = $request->get('bundleSkeleton');
                $cmd = $command;
                $cmd.= ' --bundle-name="' . $bundleName . '"';
                $cmd.= ' --namespace="' . $namespace . '"';
                $cmd.= ' --format="annotation"';
                $cmd.= ' --dir="' . $rootPath . '/src"';
                $cmd.= ' --template-name="' . $templateName . '"';
                $cmd.= ($request->get('createStructure')=='on') ? ' --structure' : '';
                break;

            // generate new controller
            case 'gui:generate:controller':
                $bundleName = $request->get('bundleName');
                $controllerName = $executor->formatControllerName($request->get('controllerName'));
                $actionNames = $request->get('controllerActions');
                $cmd = $command;
                $cmd.= ' --controller="' . $bundleName . ':' . $controllerName . '"';
                if ($actionNames)
                {
                    $actions = explode(',', $actionNames);
                    foreach ($actions as $action)
                    {
                        $cmd.= ' --actions="' . $executor->formatActionName($action) . ':'
                            . $executor->formatActionPathName($action) . '"';
                    }
                }
                $cmd.= ' --template-format="twig"';
                $cmd.= ' --route-format="annotation"';
                $cmd.= ' --no-interaction';
                break;

            // generate entity
            case 'doctrine:generate:entity':
                $bundleName = $request->get('bundleName');
                $entityName = $executor->formatEntityName($request->get('entityName'));
                $fields = $request->get('fieldName');
                $types = $request->get('fieldType');
                $cmd = $command;
                $cmd.= ' --entity="' . $bundleName . ':' . $entityName . '"';
                if (is_array($fields) && count($fields) && $fields[0])
                {
                    $cmd .= ' --fields="';
                    foreach ($fields as $key=>$field)
                    {
                        $fieldName = $executor->formatFieldName($field);
                        if ($fieldName && $fieldName != 'id' && $types[$key])
                        {
                            $cmd .= $fieldName . ':' . $types[$key] . ' ';
                        }
                    }
                    $cmd = rtrim($cmd, ' ');
                    $cmd .= '"';
                }
                $cmd.= ' --format="annotation"';
                $cmd.= ' --no-interaction';
                break;

            // generate crud
            case 'doctrine:generate:crud':
                $bundleName = $request->get('bundleName');
                $entityName = $executor->formatEntityName($request->get('entityName'));
                $cmd = 'doctrine:generate:crud';
                $cmd.= ' --entity="' . $bundleName . ':' . $entityName . '"';
                $cmd.= ' --route-prefix="' . strtolower($entityName) . '"';
                $cmd.= ' --with-write';
                $cmd.= ' --no-interaction';
                break;

            default:
                $cmd = null;
        }

        // execute command
        $ret = ($cmd) ? $executor->execute($cmd) : '';

        print_r($ret);exit;

        // create database schema
        if ($ret['errorcode'] == 0 && $command == 'gui:generate:bundle')
        {
            $ret2 = $executor->execute('doctrine:database:create');
            //$ret2 = $executor->execute('doctrine:schema:update --force');
            $ret['output'] .= '<br/>' . $ret2['output'];
        }

        // create database schema
        elseif ($ret['errorcode'] == 0 && $command == 'doctrine:generate:crud' && $request->get('createTable') == 'on')
        {
            $ret2 = $executor->execute('doctrine:database:create');
            $ret2 = $executor->execute('doctrine:schema:update --force');
            $ret['output'] .= '<br/>' . $ret2['output'];
        }

//        // install assets
//        if ($ret['errorcode'] == 0 && $command == 'gui:generate:bundle')
//        {
//            $ret2 = $executor->execute('assets:install');
//            $ret['output'] .= '<br/>' . $ret2['output'];
//        }

        // return json result
        echo json_encode($ret);
        exit;
    }

    /**
     * Get the arguments for the compoer process
     *
     * @param string $path      A root path of the project folder
     * @param string $option    A option to identify the process arguments
     * @param string $what      A optional argument for update (like: name/bundle ...)
     *
     * @return array Returns the right arguments for the composer process
     */
    private function getComposerArguments($path, $option = 'self-update', $what = '')
    {
        $phpFinder = new PhpExecutableFinder();
        $php = $phpFinder->find();
        if ($php === FALSE)
        {
            $php = '';
        }

        $composer = array();
        switch ($option)
        {
            case 'self-update':
                $composer = array(
                    $php,
                    (empty($php)) ? $path . '/bin/composer' : $path . '/bin/composer.phar',
                    'self-update'
                );
                break;
            case 'update':
                $composer = array(
                    $php,
                    (empty($php)) ? $path . '/bin/composer' : $path . '/bin/composer.phar',
                    '--no-interaction',
                    'update',
                    $what
                );
                break;
        }
        return $composer;
    }

    /**
     * Add given configuration array to config.yml.
     *
     * @param string $bundleTitle Title of the bundle
     * @param string $rootPath Path to app root
     * @param array $configuration Configuration to add
     * @return bool False if config not written
     */
    private function _addConfiguration($bundleTitle, $rootPath, $configuration)
    {
        $configFile = $rootPath . '/app/config/config.yml';

        try
        {
            // Get content of YAML file
            $ymlFileContent = file_get_contents($configFile);

            // Parse YAML file
            $config = Yaml::parse($ymlFileContent, true);
            $newConfig = array();

            if (is_array($configuration))
            {
                foreach ($configuration as $key=>$value)
                {
                    if (!isset($config[$key]))
                    {
                        $value = $this->_recursiveArrFindReplace($value, 'true', true);
                        $value = $this->_recursiveArrFindReplace($value, 'false', false);
                        $value = $this->_recursiveArrFindReplace($value, '[]', array());
                        $newConfig[$key] = $value;
                    }
                }
            }

            // new YAML config part
            $result = PHP_EOL . '# ' . $bundleTitle . ' Configuration' . PHP_EOL . Yaml::dump($newConfig, 10);

            if ($result)
            {
                echo 'Installing of configuration completed.';
                if (@file_put_contents($configFile, $result, FILE_APPEND) !== false)
                {
                    return true;
                }
            }
        }
        catch (ParseException $ex)
        {
            echo 'Unable to parse the YAML string: ' . $ex->getMessage();
        }
        catch (Exception $ex)
        {
            echo 'Exception was thrown: ' . $ex->getMessage();
        }

        return false;
    }

    /**
     * Add given routing configuration to routing.yml.
     *
     * @param string $bundleTitle Title of the bundle
     * @param string $rootPath Path to app root
     * @param array $routingEntry Configuration to add
     * @return bool False if config not written
     */
    private function _addRouting($bundleTitle, $rootPath, $routingEntry)
    {
        $routeFile = $rootPath . '/app/config/routing.yml';

        try
        {
            // Get content of YAML file
            $ymlFileContent = file_get_contents($routeFile);

            // Parse YAML file
            $routes = Yaml::parse($ymlFileContent, true);
            $newRoutes = array();

            if (!isset($routes[$routingEntry['name']]))
            {
                $newRoutes = array(
                    'resource' => $routingEntry['resource'],
                    'type' => $routingEntry['type'],
                    'prefix' => $routingEntry['prefix'],
                );
            }

            // new YAML config part
            $result = PHP_EOL . '# ' . $bundleTitle . ' Routing' . PHP_EOL . Yaml::dump($newRoutes, 10);

            if ($result)
            {
                echo 'Installing of \'' . $routingEntry['name'] . '\' completed.';
                if (@file_put_contents($routeFile, $result, FILE_APPEND) !== false)
                {
                    return true;
                }
            }
        }
        catch (ParseException $ex)
        {
            echo 'Unable to parse the YAML string: ' . $ex->getMessage();
        }
        catch (Exception $ex)
        {
            echo 'Exception was thrown: ' . $ex->getMessage();
        }

        return false;
    }

    /**
     * Search and replace given value into arrays.
     *
     * @param array $arr
     * @param string $find
     * @param string $replace
     * @return array
     */
    private function _recursiveArrFindReplace($arr, $find, $replace)
    {
        if (is_array($arr))
        {
            foreach ($arr as $key=>$val)
            {
                if (is_array($arr[$key]))
                {
                    $arr[$key] = $this->_recursiveArrFindReplace($arr[$key], $find, $replace);
                }
                else
                {
                    if ($arr[$key] == $find)
                    {
                        $arr[$key] = $replace;
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * Form to define the website style.
     *
     * @Route("/create-style/", name="guiCreateStyle")
     * @Template()
     */
    public function createStyleAction()
    {
        $output = '';
        $appFolder = $this->get('kernel')->getRootDir() . '/../app/config/';
        $lessFolder = $this->get('kernel')->getRootDir() . '/../web/' . 'bundles/gui/less/bootstrap/';
        $variablesFile = $lessFolder . 'variables.less';

        if (!is_file($variablesFile))
        {
            if (is_file($appFolder . 'variables.less'))
            {
                copy($appFolder . 'variables.less', $variablesFile);
            }
            else
            {
                copy($lessFolder . 'variables-default.less', $variablesFile);
            }
        }

        $sectionName = '';
        $helpText = '';
        $formGroupOpen = false;
        $formRowOpen = false;

        $file = fopen($variablesFile, "r");
        while(!feof($file))
        {
            $line = fgets($file);

            if (mb_strlen($line)<5) continue;

            if ($line[0] == '@')
            {
                // @ Variable
                list($name, $value) = explode(':', $line, 2);
                $name = htmlentities(trim($name));
                list($value, $comment) = explode(';', $value, 2);
                $value = htmlentities(trim($value));

                // row
                if (!$formRowOpen)
                {
                    $output .= '<div class="row"><div class="col-md-4 col-sm-4 col-xs-4">';
                }
                $formRowOpen = true;

                // group
                if ($formGroupOpen) $output .= '</div>';
                $output .= '<div class="form-group">';
                $formGroupOpen = true;

                $output .= '<label for="input-' . $name . '">' . $name . '</label>';
                $id = mb_substr($name, 1);

                // check for color fields
                if ( mb_substr($name, 0, 6) == '@gray-' || mb_substr($name, 0, 7) == '@brand-' || $name == '@gray'
                    || mb_substr($name, -6) == '-color' || (mb_substr($name, -3) == '-bg') || (mb_substr($name, -7) == '-border'))
                {
                    $color = (mb_strlen($value)>0 && $value[0]=='#') ? $value : '#FFFFFF';
                    $output .= '<div class="input-group color colorpicker" data-color="' . $color . '" data-color-format="hex">';
                    $output .= '<input id="input-' . $id . '" type="text" value="' . $value . '" name="' . $name
                        . '" class="form-control less-input-field">';
                    $output .= '<span class="input-group-addon color"><i id="color-' . $id . '" style="background-color: ' . $color . '"></i></span>';
                    $output .= '</div>';
                }
                else
                {
                    $output .= '<input id="input-' . $name . '" type="text" value="' . $value . '" name="' . $name
                        . '" class="form-control less-input-field">';
                }

                if ($helpText)
                {
                    $output .= $helpText;
                }
                $helpText = '';
            }
            elseif (mb_substr($line,0,5) == '//== ')
            {
                //== Headline
                if ($formGroupOpen)
                {
                    $output .= '</div>';
                    $formGroupOpen = false;
                }
                if ($formRowOpen)
                {
                    $output .= '</div>';
                    $output .= '<div class="col-md-8 col-sm-8 col-xs-8">'
                        . $this->_loadStyleSection($sectionName) . '</div>';
                    $output .= '</div>';
                    $formRowOpen = false;
                }

                $sectionName = trim(mb_substr($line,5));
                $output .= '<h2>' . htmlentities($sectionName) . '</h2><hr/>';
                $sectionName = strtolower($sectionName);

            }
            elseif(mb_substr($line,0,5) == '//## ')
            {
                //## Sub headline
                $output .= '<p>' . htmlentities(mb_substr($line,5)) . '</p>';
            }
            elseif(mb_substr($line,0,5) == '//** ')
            {
                //## Help
                $helpText = '<p class="help-block">' . htmlentities(mb_substr($line,5)) . '</p>';
            }


        }
        fclose($file);

        if ($formGroupOpen)
        {
            $output .= '</div>';
            $formGroupOpen = false;
        }
        if ($formRowOpen)
        {
            $output .= '</div></div>';
            $formRowOpen = false;
        }

        return array('output' => $output, 'themes' => $this->_getLessThemeNames());
    }

    /**
     * Import less variables file.
     *
     * @Route("/import-less-variables/", name="guiImportLessVariables")
     * @Method("POST")
     * @Template()
     */
    public function importLessVariablesAction(Request $request)
    {
        $lessFolder = $this->get('kernel')->getRootDir() . '/../web/' . 'bundles/gui/less/bootstrap/';

        foreach ($request->files as $file)
        {
            $ext = substr($file->getClientOriginalName(), -5);

            if ($ext != '.less')
            {
                return new Response('Only *.less files allowed', Response::HTTP_FORBIDDEN);
            }

            if (!move_uploaded_file($file->getPathname(), $lessFolder . 'variables.less'))
            {
                return new Response('Error on file upload', Response::HTTP_OK);
            }
        }

        return $this->forward('XiideaEasyGuiBundle:Default:createStyle');
    }

    /**
     * Form to define the website style.
     *
     * @Route("/apply-less-variables/", name="guiApplyLessVariables")
     * @Template()
     */
    public function applyLessVariablesAction(Request $request)
    {
        $lessFolder = $this->get('kernel')->getRootDir() . '/../web/' . 'bundles/gui/less/bootstrap/';
        $lessVariables = file_get_contents($lessFolder . 'variables.less');

        $vars = $request->request->all();

        foreach ($vars as $key=>$value)
        {
            $lessVariables = preg_replace('/^' . $key . ':(.*);/m', $key . ': ' . $value . ';', $lessVariables, 1);
        }

        file_put_contents($lessFolder . 'variables.less', $lessVariables);

        exit;
    }

    /**
     * Download bootstrap less variables file.
     *
     * @Route("/download-less-variables/", name="guiDownloadLessVariables")
     * @Template()
     */
    public function downloadLessVariablesAction()
    {
        $lessFolder = $this->get('kernel')->getRootDir() . '/../web/' . 'bundles/gui/less/bootstrap/';

        $file = $lessFolder . 'variables.less';

        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: text/less');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }

        return new Response('[' . $file . '] file not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * Save less bootstrap.css.
     *
     * @Route("/save-bootstrap-css/", name="guiSaveBootstrapCss")
     * @Template()
     */
    public function saveBootstrapCssAction(Request $request)
    {
        $cssFolder = $this->get('kernel')->getRootDir() . '/../web/css/';
        $appFolder = $this->get('kernel')->getRootDir() . '/../app/config/';
        $lessFolder = $this->get('kernel')->getRootDir() . '/../web/bundles/gui/less/bootstrap/';
        $cssValue = $request->request->get('css');

        file_put_contents($cssFolder . 'bootstrap.min.css', $cssValue);
        copy($lessFolder . 'variables.less', $appFolder . 'variables.less');

        echo 'CSS-Style saved';
        exit;
    }


    /**
     * Gets bootstrap less value.
     *
     * @Route("/get-bootstrap-less/", name="guiGetBootstrapLess")
     * @Template()
     */
    public function getBootstrapLessAction()
    {
        $lessFolder = $this->get('kernel')->getRootDir() . '/../web/bundles/gui/less/bootstrap/';
        $bootstrap = file_get_contents($lessFolder . 'bootstrap.less');

        $path = $this->container->get('templating.helper.assets')->getUrl('bundles/gui/less/bootstrap/');

        $bootstrap = preg_replace('/^@import "/m', '@import "' . $path, $bootstrap);

        echo $bootstrap;
        exit;
    }


    /**
     * Form to define the website style.
     *
     * @Route("/reset-less-variables/", name="guiResetLessVariables")
     * @Template()
     */
    public function resetLessVariablesAction(Request $request)
    {
        $theme = $request->request->get('theme', null);
        $lessFolder = $this->get('kernel')->getRootDir() . '/../web/' . 'bundles/gui/less/bootstrap/';
        $themeFolder = str_replace('\\', '/', realpath(__DIR__ . '/../Resources/lessThemes')) . '/';
        $appFolder = $this->get('kernel')->getRootDir() . '/../app/config/';

        $sourceFile = $lessFolder . 'variables-default.less';

        if ($theme && is_file($themeFolder . $theme . '.less'))
        {
            $sourceFile = $themeFolder . $theme . '.less';
        }
        elseif (!$theme && is_file($appFolder . 'variables.less'))
        {
            $sourceFile = $appFolder . 'variables.less';
        }

        copy($sourceFile, $lessFolder . 'variables.less');

        exit;
    }

    /**
     * Generates password tokens for security.yml users.
     *
     * @Route("/encrypt-password/", name="guiEncryptPassword")
     * @Template()
     */
    public function encryptPasswordAction(Request $request)
    {
        $password = '';
        $token = '';

        if ($request->request->has('passwordinput'))
        {
            $password = $request->request->get('passwordinput');
            //$token = md5($password);
            $token = hash ('sha256', $password);
        }

        return array('token' => $token, 'password' => $password);
    }

    /**
     * Loads style section templates.
     *
     * @param string $name Name of section
     * @return string
     */
    protected function _loadStyleSection($name)
    {
        $html = '';

        $name = str_replace(' ', '-', $name);

        try
        {
            $response = $this->render( 'XiideaEasyGuiBundle:StyleSections:' . $name . '.html.twig', array() );
            $html = $response->getContent();
        }
        catch(exception $e){}

        return $html;
    }

    /**
     * Gets list of all available bundle template names.
     *
     * @return array
     */
    protected function _getBundleTemplateNames()
    {
        $names = array();
        $path = str_replace('\\', '/', realpath(__DIR__ . '/../Resources/skeleton/bundle/templates'));

        if ($files = scandir($path))
        {
            foreach ($files as $file)
            {
                if ($file[0] != '.' && is_dir($path . '/' . $file))
                {
                    $names[] = $file;
                }
            }
        }

        return $names;
    }

    /**
     * Gets list of all available bundle template names.
     *
     * @return array
     */
    protected function _getLessThemeNames()
    {
        $names = array();
        $path = str_replace('\\', '/', realpath(__DIR__ . '/../Resources/lessThemes'));

        if ($files = scandir($path))
        {
            foreach ($files as $file)
            {
                if ($file[0] != '.' && is_file($path . '/' . $file))
                {
                    $names[] = basename($file, '.less');
                }
            }
        }

        return $names;
    }
}