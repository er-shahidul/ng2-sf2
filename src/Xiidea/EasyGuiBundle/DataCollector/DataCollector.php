<?php


namespace Xiidea\EasyGuiBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector AS DataCollectorBase;

class DataCollector extends DataCollectorBase
{

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }


    public function getName()
    {
        return 'xiidea_easy_gui_toolbar';
    }
}
