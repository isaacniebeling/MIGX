<?php

/**
 * @var modX $this->modx
 * @var modTemplateVar $this
 * @var array $params
 *
 * @package modx
 * @subpackage processors.element.tv.renders.mgr.input
 */
class modTemplateVarInputRenderMigx extends modTemplateVarInputRender
{
    public function process($value, array $params = array())
    {


        $namespace = 'migx';
        $this->modx->lexicon->load('tv_widget', $namespace . ':default');
        //$properties = isset($params['columns']) ? $params : $this->getProperties();
        $properties = $params;
        
        require_once dirname(dirname(dirname(dirname(__file__)))) . '/model/migx/migx.class.php';
        $this->migx = new Migx($this->modx,$properties);
        /* get input-tvs */
        $this->migx->loadConfigs();
      
        $default_formtabs = '[{"caption":"Default", "fields": [{"field":"title","caption":"Title"}]}]';
        $default_columns = '[{"header": "Title", "width": "160", "sortable": "true", "dataIndex": "title"}]';
        
        // get tabs from file or migx-config-table
        $formtabs = $this->migx->getTabs();

        if (empty($formtabs)){
            // get them from input-properties
            $formtabs = $this->modx->fromJSON($this->modx->getOption('formtabs', $properties, $default_formtabs));
            $formtabs = empty($properties['formtabs']) ? $this->modx->fromJSON($default_formtabs) : $formtabs;            
        }



        $inputTvs = $this->migx->extractInputTvs($formtabs);

        /* get base path based on either TV param or filemanager_path */
        $this->modx->getService('fileHandler', 'modFileHandler', '', array('context' => $this->modx->context->get('key')));

        $resource_array = array();
        //are we on a edit- or create-resource - managerpage?
        if (is_object($this->modx->resource)) {
            $resource_array = $this->modx->resource->toArray();
            $wctx = $this->modx->resource->get('context_key');
        } else {
            //try to get a context from REQUEST
            $wctx = isset($_REQUEST['wctx']) && !empty($_REQUEST['wctx']) ? $this->modx->sanitizeString($_REQUEST['wctx']) : '';
        }


        if (!empty($wctx)) {
            $this->migx->working_context = $wctx;
            $this->migx->source = $this->tv->getSource($wctx, false);
            //$workingContext = $this->modx->getContext($wctx);
            /*
            if (!$workingContext) {
            return $modx->error->failure($this->modx->lexicon('permission_denied'));
            }
            $wctx = $workingContext->get('key');
            */
        } else {
            //$wctx = $this->modx->context->get('key');
        }

        /* from image-TV do we need this somehow here?
        $source->setRequestProperties($_REQUEST);
        $source->initialize();
        $this->modx->controller->setPlaceholder('source',$source->get('id'));     
        */

        //$base_path = $modx->getOption('base_path', null, MODX_BASE_PATH);
        //$base_url = $modx->getOption('base_url', null, MODX_BASE_URL);
        
        $columns = $this->migx->getColumns();
        
        if (empty($columns)){
            $columns = $this->modx->fromJSON($this->modx->getOption('columns', $properties, $default_columns));
            $columns = empty($properties['columns']) ? $this->modx->fromJSON($default_columns) : $columns;            
        }


        if (is_array($columns) && count($columns) > 0) {
            foreach ($columns as $key => $column) {
                $field['name'] = $column['dataIndex'];
                $field['mapping'] = $column['dataIndex'];
                $fields[] = $field;
                $col['dataIndex'] = $column['dataIndex'];
                $col['header'] = htmlentities($column['header'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                $col['sortable'] = $column['sortable'] == 'true' ? true : false;
                $col['width'] = $column['width'];
                $col['renderer'] = $column['renderer'];
                $cols[] = $col;
                $item[$field['name']] = isset($column['default']) ? $column['default'] : '';

                if (isset($inputTvs[$field['name']]) && $tv = $this->modx->getObject('modTemplateVar', array('name' => $inputTvs[$field['name']]['inputTV']))) {

                    $inputTV = $inputTvs[$field['name']];

                    $params = $tv->get('input_properties');
                    $params['wctx'] = $wctx;
                    /*
                    if (!empty($properties['basePath'])) {
                    if ($properties['autoResourceFolders'] == 'true' && isset($resource['id'])) {
                    $params['basePath'] = $base_path.$properties['basePath'] . $resource['id'] . '/';
                    } else {
                    $params['basePath'] = $base_path.$properties['basePath'];
                    }
                    }
                    */
                    $mediasource = $this->migx->getFieldSource($inputTV, $tv);
                    $pathconfigs[$key] = '&source=' . $mediasource->get('id');
                    //$pathconfigs[$key] = '&basePath='.$params['basePath'].'&basePathRelative='.$params['basePathRelative'].'&baseUrl='.$params['baseUrl'].'&baseUrlRelative='.$params['baseUrlRelative'];

                } else {
                    $pathconfigs[$key] = array();
                }
            }
        }

        $newitem[] = $item;
        $lang = $this->modx->lexicon->fetch();
        $lang['mig_add'] = !empty($properties['btntext']) ? $properties['btntext'] : $lang['mig_add'];
        $lang['mig_add'] = str_replace("'", "\'", $lang['mig_add']);
        $this->setPlaceholder('i18n', $lang);
        $this->setPlaceholder('properties', $properties);
        $this->setPlaceholder('resource', $resource_array);
        $this->setPlaceholder('pathconfigs', $this->modx->toJSON($pathconfigs));
        $this->setPlaceholder('columns', $this->modx->toJSON($cols));
        $this->setPlaceholder('fields', $this->modx->toJSON($fields));
        $this->setPlaceholder('newitem', $this->modx->toJSON($newitem));
        $this->setPlaceholder('base_url', $this->modx->getOption('base_url'));
        $this->setPlaceholder('myctx', $wctx);
    }
    public function getTemplate()
    {
        $path = 'components/migx/';
        $corePath = $this->modx->getOption('migx.core_path', null, $this->modx->getOption('core_path') . $path);
        return $corePath . 'elements/tv/migx.tpl';
    }
}
return 'modTemplateVarInputRenderMigx';
