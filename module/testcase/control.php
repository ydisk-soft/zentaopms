<?php
/**
 * The control file of case currentModule of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     case
 * @version     $Id: control.php 5112 2013-07-12 02:51:33Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class testcase extends control
{
    public $products = array();

    /**
     * Construct function, load product, tree, user auto.
     *
     * @access public
     * @return void
     */
    public function __construct($moduleName = '', $methodName = '')
    {
        parent::__construct($moduleName, $methodName);
        $this->loadModel('product');
        $this->loadModel('tree');
        $this->loadModel('user');
        $this->view->products = $this->products = $this->product->getPairs('nocode');
        if(empty($this->products)) die($this->locate($this->createLink('product', 'showErrorNone', "fromModule=testcase")));
    }

    /**
     * Index page.
     *
     * @access public
     * @return void
     */
    public function index()
    {
        $this->locate($this->createLink('testcase', 'browse'));
    }

    /**
     * Browse cases.
     *
     * @param  int    $productID
     * @param  string $browseType
     * @param  int    $param
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function browse($productID = 0, $branch = '', $browseType = 'all', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('datatable');

        /* Set browse type. */
        $browseType = strtolower($browseType);

        /* Set browseType, productID, moduleID and queryID. */
        $productID = $this->product->saveState($productID, $this->products);
        $branch    = ($branch === '') ? (int)$this->cookie->preBranch : (int)$branch;
        setcookie('preProductID', $productID, $this->config->cookieLife, $this->config->webRoot, '', false, true);
        setcookie('preBranch', (int)$branch, $this->config->cookieLife, $this->config->webRoot, '', false, true);

        if($this->cookie->preProductID != $productID or $this->cookie->preBranch != $branch)
        {
            $_COOKIE['caseModule'] = 0;
            setcookie('caseModule', 0, 0, $this->config->webRoot, '', false, false);
        }
        if($browseType == 'bymodule') setcookie('caseModule', (int)$param, 0, $this->config->webRoot, '', false, false);
        if($browseType == 'bysuite')  setcookie('caseSuite', (int)$param, 0, $this->config->webRoot, '', false, true);
        if($browseType != 'bymodule') $this->session->set('caseBrowseType', $browseType);

        $moduleID = ($browseType == 'bymodule') ? (int)$param : ($browseType == 'bysearch' ? 0 : ($this->cookie->caseModule ? $this->cookie->caseModule : 0));
        $suiteID  = ($browseType == 'bysuite') ? (int)$param : ($browseType == 'bymodule' ? ($this->cookie->caseSuite ? $this->cookie->caseSuite : 0) : 0);
        $queryID  = ($browseType == 'bysearch') ? (int)$param : 0;

        /* Set menu, save session. */
        $this->testcase->setMenu($this->products, $productID, $branch, $moduleID, $suiteID, $orderBy);
        $this->session->set('caseList', $this->app->getURI(true));
        $this->session->set('productID', $productID);
        $this->session->set('moduleID', $moduleID);
        $this->session->set('browseType', $browseType);
        $this->session->set('orderBy', $orderBy);

        /* Load lang. */
        $this->app->loadLang('testtask');

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);
        $sort  = $this->loadModel('common')->appendOrder($orderBy);

        /* Get test cases. */
        $cases = $this->testcase->getTestCases($productID, $branch, $browseType, $browseType == 'bysearch' ? $queryID : $suiteID, $moduleID, $sort, $pager);

        /* save session .*/
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', $browseType != 'bysearch' ? false : true);

        /* Process case for check story changed. */
        $cases = $this->loadModel('story')->checkNeedConfirm($cases);
        $cases = $this->testcase->appendData($cases);

        /* Build the search form. */
        $actionURL = $this->createLink('testcase', 'browse', "productID=$productID&branch=$branch&browseType=bySearch&queryID=myQueryID");
        $this->config->testcase->search['onMenuBar'] = 'yes';
        $this->testcase->buildSearchForm($productID, $this->products, $queryID, $actionURL);

        $showModule  = !empty($this->config->datatable->testcaseBrowse->showModule) ? $this->config->datatable->testcaseBrowse->showModule : '';
        $this->view->modulePairs = $showModule ? $this->tree->getModulePairs($productID, 'case', $showModule) : array();

        /* Assign. */
        $tree = $moduleID ? $this->tree->getByID($moduleID) : '';
        $this->view->title         = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->common;
        $this->view->position[]    = html::a($this->createLink('testcase', 'browse', "productID=$productID&branch=$branch"), $this->products[$productID]);
        $this->view->position[]    = $this->lang->testcase->common;
        $this->view->productID     = $productID;
        $this->view->product       = $this->product->getById($productID);
        $this->view->productName   = $this->products[$productID];
        $this->view->modules       = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0, $branch);
        $this->view->moduleTree    = $this->tree->getTreeMenu($productID, $viewType = 'case', $startModuleID = 0, array('treeModel', 'createCaseLink'), '', $branch);
        $this->view->moduleName    = $moduleID ? $tree->name : $this->lang->tree->all;
        $this->view->moduleID      = $moduleID;
        $this->view->summary       = $this->testcase->summary($cases);
        $this->view->pager         = $pager;
        $this->view->users         = $this->user->getPairs('noletter');
        $this->view->orderBy       = $orderBy;
        $this->view->browseType    = $browseType;
        $this->view->param         = $param;
        $this->view->cases         = $cases;
        $this->view->branch        = $branch;
        $this->view->branches      = $this->loadModel('branch')->getPairs($productID);
        $this->view->suiteList     = $this->loadModel('testsuite')->getSuites($productID);
        $this->view->suiteID       = $suiteID;
        $this->view->setModule     = true;

        $this->display();
    }

    /**
     * Group case.
     *
     * @param  int    $productID
     * @param  string $groupBy
     * @access public
     * @return void
     */
    public function groupCase($productID = 0, $branch = '', $groupBy = 'story')
    {
        $groupBy   = empty($groupBy) ? 'story' : $groupBy;
        $productID = $this->product->saveState($productID, $this->products);
        if($branch === '') $branch = (int)$this->cookie->preBranch;

        $this->app->loadLang('testtask');

        $this->testcase->setMenu($this->products, $productID, $branch);
        $this->session->set('caseList', $this->app->getURI(true));

        $cases = $this->testcase->getModuleCases($productID, $branch, 0, $groupBy);
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', false);
        $cases = $this->loadModel('story')->checkNeedConfirm($cases);
        $cases = $this->testcase->appendData($cases);

        $groupCases  = array();
        $groupByList = array();
        foreach($cases as $case)
        {
            if($groupBy == 'story')
            {
                $groupCases[$case->story][] = $case;
                $groupByList[$case->story]  = $case->storyTitle;
            }
        }

        $this->app->loadLang('project');
        $this->app->loadLang('task');

        $this->view->title       = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->common;
        $this->view->position[]  = html::a($this->createLink('testcase', 'groupTask', "productID=$productID&groupBy=$groupBy"), $this->products[$productID]);
        $this->view->position[]  = $this->lang->testcase->common;
        $this->view->productID   = $productID;
        $this->view->productName = $this->products[$productID];
        $this->view->users       = $this->user->getPairs('noletter');
        $this->view->browseType  = 'group';
        $this->view->groupBy     = $groupBy;
        $this->view->orderBy     = $groupBy;
        $this->view->groupByList = $groupByList;
        $this->view->cases       = $groupCases;
        $this->view->suiteList   = $this->loadModel('testsuite')->getSuites($productID);
        $this->view->suiteID     = 0;
        $this->view->moduleID    = 0;
        $this->view->branch      = $branch;
        $this->display();
    }

    /**
     * Create a test case.
     * @param        $productID
     * @param string $branch
     * @param int    $moduleID
     * @param string $from
     * @param int    $param
     * @param int    $storyID
     * @access public
     * @return void
     */
    public function create($productID, $branch = '', $moduleID = 0, $from = '', $param = 0, $storyID = 0)
    {
        $testcaseID = $from == 'testcase' ? $param : 0;
        $bugID      = $from == 'bug' ? $param : 0;

        $this->loadModel('story');
        if(!empty($_POST))
        {
            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;

            setcookie('lastCaseModule', (int)$this->post->module, $this->config->cookieLife, $this->config->webRoot, '', false, false);
            $caseResult = $this->testcase->create($bugID);
            if(!$caseResult or dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            $caseID = $caseResult['id'];
            if($caseResult['status'] == 'exists')
            {
                $response['message'] = sprintf($this->lang->duplicate, $this->lang->testcase->common);
                $response['locate']  = $this->createLink('testcase', 'view', "caseID=$caseID");
                $this->send($response);
            }

            $this->loadModel('action');
            $this->action->create('case', $caseID, 'Opened');

            $this->executeHooks($caseID);

            /* If link from no head then reload. */
            if(isonlybody()) $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true));

            setcookie('caseModule', 0, 0, $this->config->webRoot, '', false, false);
            $response['locate'] = $this->createLink('testcase', 'browse', "productID={$this->post->product}&branch={$this->post->branch}&browseType=all&param=0&orderBy=id_desc");
            $this->send($response);
        }
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        /* Set productID and branch. */
        $productID = $this->product->saveState($productID, $this->products);
        if($branch === '') $branch = (int)$this->cookie->preBranch;

        /* Set menu. */
        $this->testcase->setMenu($this->products, $productID, $branch);

        /* Init vars. */
        $type         = 'feature';
        $stage        = '';
        $pri          = 3;
        $caseTitle    = '';
        $precondition = '';
        $keywords     = '';
        $steps        = array();
        $color        = '';

        /* If testcaseID large than 0, use this testcase as template. */
        if($testcaseID > 0)
        {
            $testcase     = $this->testcase->getById($testcaseID);
            $productID    = $testcase->product;
            $type         = $testcase->type ? $testcase->type : 'feature';
            $stage        = $testcase->stage;
            $pri          = $testcase->pri;
            $storyID      = $testcase->story;
            $caseTitle    = $testcase->title;
            $precondition = $testcase->precondition;
            $keywords     = $testcase->keywords;
            $steps        = $testcase->steps;
            $color        = $testcase->color;
        }

        /* If bugID large than 0, use this bug as template. */
        if($bugID > 0)
        {
            $bug       = $this->loadModel('bug')->getById($bugID);
            $type      = $bug->type;
            $pri       = $bug->pri ? $bug->pri : $bug->severity;
            $storyID   = $bug->story;
            $caseTitle = $bug->title;
            $keywords  = $bug->keywords;
            $steps     = $this->testcase->createStepsFromBug($bug->steps);
        }

        /* Padding the steps to the default steps count. */
        if(count($steps) < $this->config->testcase->defaultSteps)
        {
            $paddingCount = $this->config->testcase->defaultSteps - count($steps);
            $step = new stdclass();
            $step->type   = 'item';
            $step->desc   = '';
            $step->expect = '';
            for($i = 1; $i <= $paddingCount; $i ++) $steps[] = $step;
        }

        $title      = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->create;
        $position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID&branch=$branch"), $this->products[$productID]);
        $position[] = $this->lang->testcase->common;
        $position[] = $this->lang->testcase->create;

        /* Set story and currentModuleID. */
        if($storyID and empty($moduleID))
        {
            $story    = $this->loadModel('story')->getByID($storyID);
            $moduleID = $story->module;
        }
        $currentModuleID = (int)$moduleID;

        /* Get the status of stories are not closed. */
        $storyStatus = $this->lang->story->statusList;
        unset($storyStatus['closed']);
        $modules = array();
        if($currentModuleID)
        {
            $modules = $this->loadModel('tree')->getStoryModule($currentModuleID);
            $modules = $this->tree->getAllChildID($modules);
        }
        $stories = $this->story->getProductStoryPairs($productID, $branch, $modules, array_keys($storyStatus), 'id_desc', 50, 'null'); 
        if($storyID and !isset($stories[$storyID])) $stories = $this->story->formatStories(array($storyID => $story)) + $stories;//Fix bug #2406.

        /* Set custom. */
        foreach(explode(',', $this->config->testcase->customCreateFields) as $field) $customFields[$field] = $this->lang->testcase->$field;
        $this->view->customFields = $customFields;
        $this->view->showFields   = $this->config->testcase->custom->createFields;

        $this->view->title            = $title;
        $this->view->position         = $position;
        $this->view->productID        = $productID;
        $this->view->productName      = $this->products[$productID];
        $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0, $branch);
        $this->view->currentModuleID  = $currentModuleID ? $currentModuleID : (int)$this->cookie->lastCaseModule;
        $this->view->stories          = $stories;
        $this->view->caseTitle        = $caseTitle;
        $this->view->color            = $color;
        $this->view->type             = $type;
        $this->view->stage            = $stage;
        $this->view->pri              = $pri;
        $this->view->storyID          = $storyID;
        $this->view->precondition     = $precondition;
        $this->view->keywords         = $keywords;
        $this->view->steps            = $steps;
        $this->view->users            = $this->user->getPairs('noletter|noclosed|nodeleted');
        $this->view->branch           = $branch;
        $this->view->branches         = $this->session->currentProductType != 'normal' ? $this->loadModel('branch')->getPairs($productID) : array();

        $this->display();
    }


    /**
     * Create a batch test case.
     *
     * @param  int   $productID
     * @param  int   $moduleID
     * @param  int   $storyID
     * @access public
     * @return void
     */
    public function batchCreate($productID, $branch = '', $moduleID = 0, $storyID = 0)
    {
        $this->loadModel('story');
        if(!empty($_POST))
        {
            $caseID = $this->testcase->batchCreate($productID, $branch, $storyID);
            if(dao::isError()) die(js::error(dao::getError()));
            if(isonlybody()) die(js::closeModal('parent.parent', 'this'));

            setcookie('caseModule', 0, 0, $this->config->webRoot, '', false, false);
            die(js::locate($this->createLink('testcase', 'browse', "productID=$productID&branch=$branch&browseType=all&param=0&orderBy=id_desc"), 'parent'));
        }
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        /* Set productID and currentModuleID. */
        $productID = $this->product->saveState($productID, $this->products);
        if($branch === '') $branch = (int)$this->cookie->preBranch;
        if($storyID and empty($moduleID))
        {
            $story    = $this->loadModel('story')->getByID($storyID);
            $moduleID = $story->module;
        }
        $currentModuleID = (int)$moduleID;

        /* Set menu. */
        $this->testcase->setMenu($this->products, $productID, $branch);

        /* Set story list. */
        $story     = $storyID ? $this->story->getByID($storyID) : '';
        $storyList = $storyID ? array($storyID => $story->id . ':' . $story->title) : array('');

        /* Set module option menu. */
        $moduleOptionMenu          = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0, $branch);
        $moduleOptionMenu['ditto'] = $this->lang->testcase->ditto;

        /* Set custom. */
        $product = $this->product->getById($productID);
        foreach(explode(',', $this->config->testcase->customBatchCreateFields) as $field)
        {
            if($product->type != 'normal') $customFields[$product->type] = $this->lang->product->branchName[$product->type];
            $customFields[$field] = $this->lang->testcase->$field;
        }
        $showFields = $this->config->testcase->custom->batchCreateFields;
        if($product->type == 'normal')
        {
            $showFields = str_replace(array(0 => ",branch,", 1 => ",platform,"), '', ",$showFields,");
            $showFields = trim($showFields, ',');
        }
        $this->view->customFields = $customFields;
        $this->view->showFields   = $showFields;

        $this->view->title            = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->batchCreate;
        $this->view->position[]       = html::a($this->createLink('testcase', 'browse', "productID=$productID&branch=$branch"), $this->products[$productID]);
        $this->view->position[]       = $this->lang->testcase->common;
        $this->view->position[]       = $this->lang->testcase->batchCreate;
        $this->view->product          = $product;
        $this->view->productID        = $productID;
        $this->view->story            = $story;
        $this->view->storyList        = $storyList;
        $this->view->productName      = $this->products[$productID];
        $this->view->moduleOptionMenu = $moduleOptionMenu;
        $this->view->currentModuleID  = $currentModuleID;
        $this->view->branch           = $branch;
        $this->view->branches         = $this->loadModel('branch')->getPairs($productID);
        $this->view->needReview       = $this->testcase->forceNotReview() == true ? 0 : 1;

        $this->display();
    }

    /**
     * Create bug.
     *
     * @param  int    $productID
     * @param  string $extras
     * @access public
     * @return void
     */
    public function createBug($productID, $branch = 0, $extras = '')
    {
        parse_str(str_replace(array(',', ' '), array('&', ''), $extras));

        $this->loadModel('testtask');
        $case = '';
        if($runID)
        {
            $case    = $this->testtask->getRunById($runID)->case;
            $results = $this->testtask->getResults($runID);
        }
        elseif($caseID)
        {
            $case    = $this->testcase->getById($caseID);
            $results = $this->testtask->getResults(0, $caseID);
        }

        if(!$case) die(js::error($this->lang->notFound) . js::locate('back', 'parent'));

        $this->view->title   = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->createBug;
        $this->view->runID   = $runID;
        $this->view->case    = $case;
        $this->view->caseID  = $caseID;
        $this->view->version = $version;
        $this->display();
    }

    /**
     * View a test case.
     *
     * @param  int    $caseID
     * @param  int    $version
     * @param  string $from
     * @access public
     * @return void
     */
    public function view($caseID, $version = 0, $from = 'testcase', $taskID = 0)
    {
        $case = $this->testcase->getById($caseID, $version);
        if(!$case) die(js::error($this->lang->notFound) . js::locate('back'));
        if($from == 'testtask')
        {
            $run = $this->loadModel('testtask')->getRunByCase($taskID, $caseID);
            $case->assignedTo    = $run->assignedTo;
            $case->lastRunner    = $run->lastRunner;
            $case->lastRunDate   = $run->lastRunDate;
            $case->lastRunResult = $run->lastRunResult;
            $case->caseStatus    = $case->status;
            $case->status        = $run->status;
        }

        $branches  = $this->session->currentProductType == 'normal' ? array() : $this->loadModel('branch')->getPairs($case->product);
        $isLibCase = ($case->lib and empty($case->product));
        if($isLibCase)
        {
            $libraries = $this->loadModel('caselib')->getLibraries();
            $this->caselib->setLibMenu($libraries, $case->lib);
            $this->lang->testcase->menu = $this->lang->caselib->menu;

            $this->view->title      = "CASE #$case->id $case->title - " . $libraries[$case->lib];
            $this->view->position[] = html::a($this->createLink('caselib', 'browse', "libID=$case->lib"), $libraries[$case->lib]);

            $this->view->libName = $libraries[$case->lib];
        }
        else
        {
            $productID = $case->product;
            $this->testcase->setMenu($this->products, $productID, $case->branch);

            $this->view->title      = "CASE #$case->id $case->title - " . $this->products[$productID];
            $this->view->position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);

            $this->view->productName = $this->products[$productID];
            $this->view->branchName  = $this->session->currentProductType == 'normal' ? '' : zget($branches, $case->branch, '');
        }

        $caseFails = $this->dao->select('COUNT(*) AS count')->from(TABLE_TESTRESULT)
            ->where('caseResult')->eq('fail')
            ->andwhere('`case`')->eq($caseID)
            ->beginIF($from == 'testtask')->andwhere('`run`')->eq($taskID)->fi()
            ->fetch('count');
        $case->caseFails = $caseFails;

        $this->executeHooks($caseID);

        $this->view->position[] = $this->lang->testcase->common;
        $this->view->position[] = $this->lang->testcase->view;

        $this->view->case       = $case;
        $this->view->from       = $from;
        $this->view->taskID     = $taskID;
        $this->view->version    = $version ? $version : $case->version;
        $this->view->modulePath = $this->tree->getParents($case->module);
        $this->view->caseModule = empty($case->module) ? '' : $this->tree->getById($case->module);
        $this->view->users      = $this->user->getPairs('noletter');
        $this->view->actions    = $this->loadModel('action')->getList('case', $caseID);
        $this->view->preAndNext = $this->loadModel('common')->getPreAndNextObject('testcase', $caseID);
        $this->view->runID      = $from == 'testcase' ? 0 : $run->id;
        $this->view->isLibCase  = $isLibCase;
        $this->view->caseFails  = $caseFails;
        $this->view->branches   = $branches;

        $this->display();
    }

    /**
     * Edit a case.
     *
     * @param  int   $caseID
     * @access public
     * @return void
     */
    public function edit($caseID, $comment = false)
    {
        $this->loadModel('story');

        if(!empty($_POST))
        {
            $changes = array();
            $files   = array();
            if($comment == false)
            {
                $changes = $this->testcase->update($caseID);
                if(dao::isError()) die(js::error(dao::getError()));
                $files = $this->loadModel('file')->saveUpload('testcase', $caseID);
            }
            if($this->post->comment != '' or !empty($changes) or !empty($files))
            {
                $this->loadModel('action');
                $action = !empty($changes) ? 'Edited' : 'Commented';
                $fileAction = '';
                if(!empty($files)) $fileAction = $this->lang->addFiles . join(',', $files) . "\n";
                $actionID = $this->action->create('case', $caseID, $action, $fileAction . $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $this->executeHooks($caseID);

            die(js::locate($this->createLink('testcase', 'view', "caseID=$caseID"), 'parent'));
        }

        $case = $this->testcase->getById($caseID);
        if(empty($case->steps))
        {
            $step = new stdclass();
            $step->type   = 'step';
            $step->desc   = '';
            $step->expect = '';
            $case->steps[] = $step;
        }

        $isLibCase = ($case->lib and empty($case->product));
        if($isLibCase)
        {
            $libraries = $this->loadModel('caselib')->getLibraries();
            $this->caselib->setLibMenu($libraries, $case->lib);
            $this->lang->testcase->menu = $this->lang->testsuite->menu;
            if($this->config->global->flow == 'onlyTest') $this->lang->menugroup->testcase = 'caselib';

            $title      = "CASE #$case->id $case->title - " . $libraries[$case->lib];
            $position[] = html::a($this->createLink('caselib', 'browse', "libID=$case->lib"), $libraries[$case->lib]);

            $this->view->libID     = $case->lib;
            $this->view->libName   = $libraries[$case->lib];
            $this->view->libraries = $libraries;
            $this->view->moduleOptionMenu = $this->tree->getOptionMenu($case->lib, $viewType = 'caselib', $startModuleID = 0);
        }
        else
        {
            $productID  = $case->product;
            $title      = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->edit;
            $position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);

            /* Set menu. */
            $this->testcase->setMenu($this->products, $productID, $case->branch);

            $moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0, $case->branch);
            if($case->lib and $case->fromCaseID)
            {
                $libName    = $this->loadModel('caselib')->getById($case->lib)->name;
                $libModules = $this->tree->getOptionMenu($case->lib, 'caselib');
                foreach($libModules as $moduleID => $moduleName)
                {
                    if($moduleID == 0) continue;
                    $moduleOptionMenu[$moduleID] = $libName . $moduleName;
                }
            }

            $this->view->productID        = $productID;
            $this->view->branches         = $this->session->currentProductType == 'normal' ? array() : $this->loadModel('branch')->getPairs($productID);
            $this->view->productName      = $this->products[$productID];
            $this->view->moduleOptionMenu = $moduleOptionMenu;
            $this->view->stories          = $this->story->getProductStoryPairs($productID, $case->branch);
        }
        if($this->testcase->forceNotReview()) unset($this->lang->testcase->statusList['wait']);
        $position[]      = $this->lang->testcase->common;
        $position[]      = $this->lang->testcase->edit;

        $this->view->title           = $title;
        $this->view->position        = $position;
        $this->view->currentModuleID = $case->module;
        $this->view->users           = $this->user->getPairs('noletter');
        $this->view->case            = $case;
        $this->view->actions         = $this->loadModel('action')->getList('case', $caseID);
        $this->view->isLibCase       = $isLibCase;

        $this->display();
    }

    /**
     * Batch edit case.
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function batchEdit($productID = 0, $branch = 0, $type = 'case')
    {
        if($this->post->title)
        {
            $allChanges = $this->testcase->batchUpdate();
            if($allChanges)
            {
                foreach($allChanges as $caseID => $changes )
                {
                    if(empty($changes)) continue;

                    $actionID = $this->loadModel('action')->create('case', $caseID, 'Edited');
                    $this->action->logHistory($actionID, $changes);
                }
            }

            die(js::locate($this->session->caseList, 'parent'));
        }

        $caseIDList = $this->post->caseIDList ? $this->post->caseIDList : die(js::locate($this->session->caseList));
        $caseIDList = array_unique($caseIDList);

        /* Get the edited cases. */
        $cases = $this->testcase->getByList($caseIDList);
        $branchProduct = false;

        /* The cases of a product. */
        if($productID)
        {
            if($type == 'lib')
            {
                $libID     = $productID;
                $libraries = $this->loadModel('caselib')->getLibraries();
                $this->caselib->setLibMenu($libraries, $libID);
                $this->lang->testcase->menu = $this->lang->testsuite->menu;

                /* Set modules. */
                $modules = $this->tree->getOptionMenu($libID, $viewType = 'caselib', $startModuleID = 0, $branch);
                $modules = array('ditto' => $this->lang->testcase->ditto) + $modules;

                $this->view->modules    = $modules;
                $this->view->title      = $libraries[$libID] . $this->lang->colon . $this->lang->testcase->batchEdit;
                $this->view->position[] = html::a($this->createLink('caselib', 'browse', "libID=$libID"), $libraries[$libID]);
            }
            else
            {
                $product = $this->product->getByID($productID);
                $this->testcase->setMenu($this->products, $productID, $branch);

                if($product->type != 'normal') $branchProduct = true;

                /* Set modules. */
                $modules = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0, $branch);
                $modules = array('ditto' => $this->lang->testcase->ditto) + $modules;

                $this->view->branches   = $product->type == 'normal' ? array() : $this->loadModel('branch')->getPairs($product->id);
                $this->view->modules    = $modules;
                $this->view->position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
                $this->view->title      = $product->name . $this->lang->colon . $this->lang->testcase->batchEdit;
            }
        }
        /* The cases of my. */
        else
        {
            $this->lang->testcase->menu = $this->lang->my->menu;
            $this->lang->set('menugroup.testcase', 'my');
            $this->lang->testcase->menuOrder = $this->lang->my->menuOrder;
            $this->loadModel('my')->setMenu();

            $this->view->position[] = html::a($this->server->http_referer, $this->lang->my->testCase);
            $this->view->title      = $this->lang->testcase->batchEdit;

            /* Set modules. */
            $productIdList = array();
            foreach($cases as $case) $productIdList[$case->product] = $case->product;

            $products = $this->product->getByIdList($productIdList);
            foreach($products as $product)
            {
                if($product->type != 'normal')
                {
                    $branchProduct = true;
                    break;
                }
            }
        }

        // if(!$this->testcase->forceNotReview()) unset($this->lang->testcase->statusList['wait']); /* Bug#1343 */

        /* Judge whether the editedTasks is too large and set session. */
        $countInputVars = count($cases) * (count(explode(',', $this->config->testcase->custom->batchEditFields)) + 3);
        $showSuhosinInfo = common::judgeSuhosinSetting($countInputVars);
        if($showSuhosinInfo) $this->view->suhosinInfo = extension_loaded('suhosin') ? sprintf($this->lang->suhosinInfo, $countInputVars) : sprintf($this->lang->maxVarsInfo, $countInputVars);

        $this->loadModel('story');
        $stories = $this->story->getProductStoryPairs($productID, $branch);
        $this->view->stories = array('' => '', 'ditto' => $this->lang->testcase->ditto) + $stories;

        /* Set custom. */
        foreach(explode(',', $this->config->testcase->customBatchEditFields) as $field) $customFields[$field] = $this->lang->testcase->$field;
        $this->view->customFields = $customFields;
        $this->view->showFields   = $this->config->testcase->custom->batchEditFields;

        /* Assign. */
        $this->view->position[]    = $this->lang->testcase->common;
        $this->view->position[]    = $this->lang->testcase->batchEdit;
        $this->view->caseIDList    = $caseIDList;
        $this->view->productID     = $productID;
        $this->view->branchProduct = $branchProduct;
        $this->view->priList       = array('ditto' => $this->lang->testcase->ditto) + $this->lang->testcase->priList;
        $this->view->typeList      = array('' => '', 'ditto' => $this->lang->testcase->ditto) + $this->lang->testcase->typeList;
        $this->view->cases         = $cases;

        $this->display();
    }

    /**
     * Review case.
     *
     * @param  int    $caseID
     * @access public
     * @return void
     */
    public function review($caseID)
    {
        if($_POST)
        {
            $changes = $this->testcase->review($caseID);
            if(dao::isError()) die(js::error(dao::getError()));

            if($changes)
            {
                $result = $this->post->result;
                $actionID = $this->loadModel('action')->create('case', $caseID, 'Reviewed', $this->post->comment, ucfirst($result));
                $this->action->logHistory($actionID, $changes);
            }

            $this->executeHooks($caseID);

            die(js::reload('parent.parent'));
        }

        $this->view->users   = $this->user->getPairs('noletter|noclosed|nodeleted');
        $this->view->case    = $this->testcase->getById($caseID);
        $this->view->actions = $this->loadModel('action')->getList('case', $caseID);
        $this->display();
    }

    /**
     * Batch review case.
     *
     * @param  string $result
     * @access public
     * @return void
     */
    public function batchReview($result)
    {
        $caseIdList = $this->post->caseIDList ? $this->post->caseIDList : die(js::locate($this->session->caseList, 'parent'));
        $caseIdList = array_unique($caseIdList);
        $actions    = $this->testcase->batchReview($caseIdList, $result);

        if(dao::isError()) die(js::error(dao::getError()));
        die(js::locate($this->session->caseList, 'parent'));
    }

    /**
     * Delete a test case
     *
     * @param  int    $caseID
     * @param  string $confirm yes|noe
     * @access public
     * @return void
     */
    public function delete($caseID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            die(js::confirm($this->lang->testcase->confirmDelete, inlink('delete', "caseID=$caseID&confirm=yes")));
        }
        else
        {
            $this->testcase->delete(TABLE_CASE, $caseID);

            $this->executeHooks($caseID);

            /* if ajax request, send result. */
            if($this->server->ajax)
            {
                if(dao::isError())
                {
                    $response['result']  = 'fail';
                    $response['message'] = dao::getError();
                }
                else
                {
                    $response['result']  = 'success';
                    $response['message'] = '';
                }
                $this->send($response);
            }
            die(js::locate($this->session->caseList, 'parent'));
        }
    }

    /**
     * Batch delete cases.
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function batchDelete($productID = 0)
    {
        $caseIDList = $this->post->caseIDList ? $this->post->caseIDList : die(js::locate($this->session->caseList));
        $caseIDList = array_unique($caseIDList);

        foreach($caseIDList as $caseID) $this->testcase->delete(TABLE_CASE, $caseID);
        die(js::locate($this->session->caseList));
    }

    /**
     * Batch change branch.
     *
     * @param  int    $branchID
     * @access public
     * @return void
     */
    public function batchChangeBranch($branchID)
    {
        if($this->post->caseIDList)
        {
            $caseIDList = $this->post->caseIDList;
            $caseIDList = array_unique($caseIDList);
            unset($_POST['caseIDList']);
            $allChanges = $this->testcase->batchChangeBranch($caseIDList, $branchID);
            if(dao::isError()) die(js::error(dao::getError()));
            foreach($allChanges as $caseID => $changes)
            {
                $this->loadModel('action');
                $actionID = $this->action->create('case', $caseID, 'Edited');
                $this->action->logHistory($actionID, $changes);
            }
        }

        die(js::locate($this->session->caseList, 'parent'));
    }

    /**
     * Batch change the module of case.
     *
     * @param  int    $moduleID
     * @access public
     * @return void
     */
    public function batchChangeModule($moduleID)
    {
        if($this->post->caseIDList)
        {
            $caseIDList = $this->post->caseIDList;
            $caseIDList = array_unique($caseIDList);
            unset($_POST['caseIDList']);
            $allChanges = $this->testcase->batchChangeModule($caseIDList, $moduleID);
            if(dao::isError()) die(js::error(dao::getError()));
            foreach($allChanges as $caseID => $changes)
            {
                $this->loadModel('action');
                $actionID = $this->action->create('case', $caseID, 'Edited');
                $this->action->logHistory($actionID, $changes);
            }
        }

        die(js::locate($this->session->caseList, 'parent'));
    }

    /**
     * Batch review case.
     *
     * @param  string $result
     * @access public
     * @return void
     */
    public function batchCaseTypeChange($result)
    {
        $caseIdList = $this->post->caseIDList ? $this->post->caseIDList : die(js::locate($this->session->caseList, 'parent'));
        $caseIDList = array_unique($caseIDList);
        $this->testcase->batchCaseTypeChange($caseIdList, $result);

        if(dao::isError()) die(js::error(dao::getError()));
        die(js::locate($this->session->caseList, 'parent'));
    }

    /**
     * Link related cases.
     *
     * @param  int    $caseID
     * @param  string $browseType
     * @param  int    $param
     * @access public
     * @return void
     */
    public function linkCases($caseID, $browseType = '', $param = 0)
    {
        /* Get case and queryID. */
        $case    = $this->testcase->getById($caseID);
        $queryID = ($browseType == 'bySearch') ? (int)$param : 0;

        /* Set menu. */
        $this->testcase->setMenu($this->products, $case->product, $case->branch);

        /* Build the search form. */
        $actionURL = $this->createLink('testcase', 'linkCases', "caseID=$caseID&browseType=bySearch&queryID=myQueryID", '', true);
        $this->testcase->buildSearchForm($case->product, $this->products, $queryID, $actionURL);

        /* Get cases to link. */
        $cases2Link = $this->testcase->getCases2Link($caseID, $browseType, $queryID);

        /* Assign. */
        $this->view->title      = $case->title . $this->lang->colon . $this->lang->testcase->linkCases;
        $this->view->position[] = html::a($this->createLink('product', 'view', "productID=$case->product"), $this->products[$case->product]);
        $this->view->position[] = html::a($this->createLink('testcase', 'view', "caseID=$caseID"), $case->title);
        $this->view->position[] = $this->lang->testcase->linkCases;
        $this->view->case       = $case;
        $this->view->cases2Link = $cases2Link;
        $this->view->users      = $this->loadModel('user')->getPairs('noletter');

        $this->display();
    }

    /**
     * Confirm testcase changed.
     *
     * @param  int    $caseID
     * @param  int    $taskID
     * @param  string $from
     * @access public
     * @return void
     */
    public function confirmChange($caseID, $taskID = 0, $from = 'view')
    {
        $case = $this->testcase->getById($caseID);
        $this->dao->update(TABLE_TESTRUN)->set('version')->eq($case->version)->where('`case`')->eq($caseID)->exec();
        if($from == 'view') die(js::locate(inlink('view', "caseID=$caseID&version=$case->version&from=testtask&taskID=$taskID"), 'parent'));
        die(js::reload('parent'));
    }

    /**
     * Confirm libcase changed.
     *
     * @param  int    $caseID
     * @param  int    $libcaseID
     * @param  int    $version
     * @access public
     * @return void
     */
    public function confirmLibcaseChange($caseID, $libcaseID)
    {
        $case    = $this->testcase->getById($caseID);
        $libCase = $this->testcase->getById($libcaseID);
        $version = $case->version + 1;
        $this->dao->update(TABLE_CASE)->set('version')->eq($version)->set('fromCaseVersion')->eq($version)->where('id')->eq($caseID)->exec();
        foreach($libCase->steps as $step)
        {
            unset($step->id);
            $step->case    = $caseID;
            $step->version = $version;
            $this->dao->insert(TABLE_CASESTEP)->data($step)->exec();
        }
        die(js::locate($this->createLink('testcase', 'view', "caseID=$caseID&version=$version"), 'parent'));
    }

    /**
     * Ignore libcase changed.
     *
     * @param  int    $caseID
     * @access public
     * @return void
     */
    public function ignoreLibcaseChange($caseID)
    {
        $case    = $this->testcase->getById($caseID);
        $this->dao->update(TABLE_CASE)->set('fromCaseVersion')->eq($case->version)->where('id')->eq($caseID)->exec();
        die(js::reload('parent'));
    }

    /**
     * Confirm story changes.
     *
     * @param  int    $caseID
     * @access public
     * @return void
     */
    public function confirmStoryChange($caseID,$reload=true)
    {
        $case = $this->testcase->getById($caseID);
        $this->dao->update(TABLE_CASE)->set('storyVersion')->eq($case->latestStoryVersion)->where('id')->eq($caseID)->exec();
        $this->loadModel('action')->create('case', $caseID, 'confirmed', '', $case->latestStoryVersion);
        if($reload) die(js::reload('parent'));
    }

    /**
     * Batch ctory change cases.
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function batchConfirmStoryChange($productID = 0)
    {
        $caseIDList = $this->post->caseIDList ? $this->post->caseIDList : die(js::locate($this->session->caseList));
        $caseIDList = array_unique($caseIDList);

        foreach($caseIDList as $caseID) $this->confirmStoryChange($caseID,false);
        die(js::locate($this->session->caseList));
    }

    /**
     * export
     *
     * @param  int    $productID
     * @param  string $orderBy
     * @param  int    $taskID
     * @param  string $browseType
     * @access public
     * @return void
     */
    public function export($productID, $orderBy, $taskID = 0, $browseType = '')
    {
        $product = $this->loadModel('product')->getById($productID);
        if($product->type != 'normal') $this->lang->testcase->branch = $this->lang->product->branchName[$product->type];
        if($_POST)
        {
            $this->app->loadLang('testtask');
            $caseLang   = $this->lang->testcase;
            $caseConfig = $this->config->testcase;

            /* Create field lists. */
            $fields  = $this->post->exportFields ? $this->post->exportFields : explode(',', $caseConfig->exportFields);
            foreach($fields as $key => $fieldName)
            {
                $fieldName = trim($fieldName);
                if(!($product->type == 'normal' and $fieldName == 'branch'))
                {
                    $fields[$fieldName] = isset($caseLang->$fieldName) ? $caseLang->$fieldName : $fieldName;
                }
                unset($fields[$key]);
            }

            /* Get cases. */
            if($this->session->testcaseOnlyCondition)
            {
                if($taskID)
                {
                    $caseIDList = $this->dao->select('`case`')->from(TABLE_TESTRUN)->where('task')->eq($taskID)->fetchPairs();
                    $cases = $this->dao->select('*')->from(TABLE_CASE)->where($this->session->testcaseQueryCondition)->andWhere('id')->in($caseIDList)
                        ->beginIF($this->post->exportType == 'selected')->andWhere('id')->in($this->cookie->checkedItem)->fi()
                        ->orderBy($orderBy)->fetchAll('id');
                }
                else
                {
                    $cases = $this->dao->select('*')->from(TABLE_CASE)->where($this->session->testcaseQueryCondition)
                        ->beginIF($this->post->exportType == 'selected')->andWhere('id')->in($this->cookie->checkedItem)->fi()
                        ->orderBy($orderBy)->fetchAll('id');
                }
            }
            else
            {
                $cases   = array();
                $orderBy = " ORDER BY " . str_replace(array('|', '^A', '_'), ' ', $orderBy);
                $stmt    = $this->dbh->query($this->session->testcaseQueryCondition . $orderBy);
                while($row = $stmt->fetch())
                {
                    $caseID = isset($row->case) ? $row->case : $row->id;
                    if($this->post->exportType == 'selected' and strpos(",{$this->cookie->checkedItem},", ",$caseID,") === false) continue;
                    $cases[$caseID] = $row;
                    $row->id        = $caseID;
                }
            }
            if($taskID) $caseLang->statusList = $this->lang->testtask->statusList;

            $stmt = $this->dao->select('t1.*')->from(TABLE_TESTRESULT)->alias('t1')
                ->leftJoin(TABLE_TESTRUN)->alias('t2')->on('t1.run=t2.id')
                ->where('t1.`case`')->in(array_keys($cases))
                ->beginIF($taskID)->andWhere('t2.task')->eq($taskID)->fi()
                ->orderBy('id_desc')
                ->query();
            $results = array();
            while($result = $stmt->fetch())
            {
                if(!isset($results[$result->case])) $results[$result->case] = unserialize($result->stepResults);
            }

            /* Get users, products and projects. */
            $users    = $this->loadModel('user')->getPairs('noletter');
            $products = $this->loadModel('product')->getPairs('nocode');
            $branches = $this->loadModel('branch')->getPairs($productID);

            /* Get related objects id lists. */
            $relatedStoryIdList  = array();
            $relatedCaseIdList   = array();

            foreach($cases as $case)
            {
                $relatedStoryIdList[$case->story]   = $case->story;
                $relatedCaseIdList[$case->linkCase] = $case->linkCase;

                /* Process link cases. */
                $linkCases = explode(',', $case->linkCase);
                foreach($linkCases as $linkCaseID)
                {
                    if($linkCaseID) $relatedCaseIdList[$linkCaseID] = trim($linkCaseID);
                }
            }

            /* Get related objects title or names. */
            $relatedModules = $this->loadModel('tree')->getAllModulePairs('case');
            $relatedStories = $this->dao->select('id,title')->from(TABLE_STORY) ->where('id')->in($relatedStoryIdList)->fetchPairs();
            $relatedCases   = $this->dao->select('id, title')->from(TABLE_CASE)->where('id')->in($relatedCaseIdList)->fetchPairs();
            $relatedSteps   = $this->dao->select('id,parent,`case`,version,type,`desc`,expect')->from(TABLE_CASESTEP)->where('`case`')->in(@array_keys($cases))->orderBy('version desc,id')->fetchGroup('case', 'id');

            $cases = $this->testcase->appendData($cases);
            foreach($cases as $case)
            {
                $case->stepDesc   = '';
                $case->stepExpect = '';
                $case->real       = '';
                $result = isset($results[$case->id]) ? $results[$case->id] : array();
                $case->real = empty($result) ? '' : $result[0]['real'];
                if(isset($relatedSteps[$case->id]))
                {
                    $i = $childId = 0;
                    foreach($relatedSteps[$case->id] as $step)
                    {
                        $stepId = 0;
                        if($step->type == 'group' or $step->type == 'step')
                        {
                            $i++;
                            $childId = 0;
                            $stepId  = $i;
                        }
                        else
                        {
                            $stepId = $i . '.' . $childId;
                        }
                        if($step->version != $case->version) continue;
                        $sign = (in_array($this->post->fileType, array('html', 'xml'))) ? '<br />' : "\n";
                        $case->stepDesc   .= $stepId . ". " . $step->desc . $sign;
                        $case->stepExpect .= $stepId . ". " . $step->expect . $sign;
                        $case->real .= $stepId . ". " . (isset($result[$step->id]) ? $result[$step->id]['real'] : '') . $sign;
                        $childId ++;
                    }
                }
                $case->stepDesc   = trim($case->stepDesc);
                $case->stepExpect = trim($case->stepExpect);
                $case->real       = trim($case->real);

                if($this->post->fileType == 'csv')
                {
                    $case->stepDesc   = str_replace('"', '""', $case->stepDesc);
                    $case->stepExpect = str_replace('"', '""', $case->stepExpect);
                }

                /* fill some field with useful value. */
                $case->product = !isset($products[$case->product])     ? '' : $products[$case->product] . "(#$case->product)";
                $case->branch  = !isset($branches[$case->branch])      ? '' : $branches[$case->branch] . "(#$case->branch)";
                $case->module  = !isset($relatedModules[$case->module])? '' : $relatedModules[$case->module] . "(#$case->module)";
                $case->story   = !isset($relatedStories[$case->story]) ? '' : $relatedStories[$case->story] . "(#$case->story)";

                if(isset($caseLang->priList[$case->pri]))              $case->pri           = $caseLang->priList[$case->pri];
                if(isset($caseLang->typeList[$case->type]))            $case->type          = $caseLang->typeList[$case->type];
                if(isset($caseLang->statusList[$case->status]))        $case->status        = $this->processStatus('testcase', $case);
                if(isset($users[$case->openedBy]))                     $case->openedBy      = $users[$case->openedBy];
                if(isset($users[$case->lastEditedBy]))                 $case->lastEditedBy  = $users[$case->lastEditedBy];
                if(isset($caseLang->resultList[$case->lastRunResult])) $case->lastRunResult = $caseLang->resultList[$case->lastRunResult];

                $case->bugsAB       = $case->bugs;       unset($case->bugs);
                $case->resultsAB    = $case->results;    unset($case->results);
                $case->stepNumberAB = $case->stepNumber; unset($case->stepNumber);
                unset($case->caseFails);

                $case->stage = explode(',', $case->stage);
                foreach($case->stage as $key => $stage) $case->stage[$key] = isset($caseLang->stageList[$stage]) ? $caseLang->stageList[$stage] : $stage;
                $case->stage = join("\n", $case->stage);

                $case->openedDate     = substr($case->openedDate, 0, 10);
                $case->lastEditedDate = substr($case->lastEditedDate, 0, 10);

                if($case->linkCase)
                {
                    $tmpLinkCases = array();
                    $linkCaseIdList = explode(',', $case->linkCase);
                    foreach($linkCaseIdList as $linkCaseID)
                    {
                        $linkCaseID = trim($linkCaseID);
                        $tmpLinkCases[] = isset($relatedCases[$linkCaseID]) ? $relatedCases[$linkCaseID] . "(#$linkCaseID)" : $linkCaseID;
                    }
                    $case->linkCase = join("; \n", $tmpLinkCases);
                }
            }
            if(isset($this->config->bizVersion)) list($fields, $cases) = $this->loadModel('workflowfield')->appendDataFromFlow($fields, $cases);

            $this->post->set('fields', $fields);
            $this->post->set('rows', $cases);
            $this->post->set('kind', 'testcase');
            $this->fetch('file', 'export2' . $this->post->fileType, $_POST);
        }

        $fileName    = $this->lang->testcase->common;
        $productName = $this->dao->findById($productID)->from(TABLE_PRODUCT)->fetch('name');
        $browseType  = isset($this->lang->testcase->featureBar['browse'][$browseType]) ? $this->lang->testcase->featureBar['browse'][$browseType] : '';

        if($taskID) $taskName = $this->dao->findById($taskID)->from(TABLE_TESTTASK)->fetch('name');

        $this->view->fileName        = $productName . $this->lang->dash . ($taskID ? $taskName . $this->lang->dash : '') . $browseType . $fileName;
        $this->view->allExportFields = $this->config->testcase->exportFields;
        $this->view->customExport    = true;
        $this->display();
    }

    /**
     * Export templet
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function exportTemplet($productID)
    {
        if($_POST)
        {
            $product = $this->loadModel('product')->getById($productID);

            if($product->type != 'normal') $fields['branch'] = $this->lang->product->branchName[$product->type];
            $fields['module']       = $this->lang->testcase->module;
            $fields['title']        = $this->lang->testcase->title;
            $fields['precondition'] = $this->lang->testcase->precondition;
            $fields['stepDesc']     = $this->lang->testcase->stepDesc;
            $fields['stepExpect']   = $this->lang->testcase->stepExpect;
            $fields['keywords']     = $this->lang->testcase->keywords;
            $fields['pri']          = $this->lang->testcase->pri;
            $fields['type']         = $this->lang->testcase->type;
            $fields['stage']        = $this->lang->testcase->stage;

            $fields[''] = '';
            $fields['typeValue']  = $this->lang->testcase->lblTypeValue;
            $fields['stageValue'] = $this->lang->testcase->lblStageValue;
            if($product->type != 'normal') $fields['branchValue'] = $this->lang->product->branchName[$product->type];

            $branches = $this->loadModel('branch')->getPairs($productID);
            foreach($branches as $branchID => $branchName) $branches[$branchID] = $branchName . "(#$branchID)";

            $modules = $this->loadModel('tree')->getOptionMenu($productID, 'case');
            $rows    = array();
            $num     = (int)$this->post->num;
            for($i = 0; $i < $num; $i++)
            {
                foreach($modules as $moduleID => $module)
                {
                    $row = new stdclass();
                    $row->module     = $module . "(#$moduleID)";
                    $row->stepDesc   = "1. \n2. \n3.";
                    $row->stepExpect = "1. \n2. \n3.";

                    if(empty($rows))
                    {
                        $row->typeValue   = join("\n", $this->lang->testcase->typeList);
                        $row->stageValue  = join("\n", $this->lang->testcase->stageList);
                        if($product->type != 'normal') $row->branchValue = join("\n", $branches);
                    }
                    $rows[] = $row;
                }
            }

            $this->post->set('fields', $fields);
            $this->post->set('kind', 'testcase');
            $this->post->set('rows', $rows);
            $this->post->set('extraNum', $num);
            $this->post->set('fileName', 'templet');
            $this->fetch('file', 'export2csv', $_POST);
        }

        $this->display();
    }

    /**
     * Import csv
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function import($productID, $branch = 0)
    {
        if($_FILES)
        {
            $file = $this->loadModel('file')->getUpload('file');
            $file = $file[0];

            $fileName = $this->file->savePath . $this->file->getSaveName($file['pathname']);
            move_uploaded_file($file['tmpname'], $fileName);

            $rows   = $this->file->parseCSV($fileName);
            $fields = $this->testcase->getImportFields($productID);
            $fields = array_flip($fields);
            $header = array();
            foreach($rows[0] as $i => $rowValue)
            {
                if(empty($rowValue)) break;
                $header[$i] = $rowValue;
            }
            unset($rows[0]);

            $columnKey = array();
            foreach($header as $title)
            {
                if(!isset($fields[$title])) continue;
                $columnKey[] = $fields[$title];
            }

            if(count($columnKey) <= 3 or $this->post->encode != 'utf-8')
            {
                $fc     = file_get_contents($fileName);
                $encode = $this->post->encode != "utf-8" ? $this->post->encode : 'gbk';
                $fc     = helper::convertEncoding($fc, $encode, 'utf-8');
                file_put_contents($fileName, $fc);

                $rows      = $this->file->parseCSV($fileName);
                $columnKey = array();
                $header    = array();
                foreach($rows[0] as $i => $rowValue)
                {
                    if(empty($rowValue)) break;
                    $header[$i] = $rowValue;
                }
                unset($rows[0]);
                foreach($header as $title)
                {
                    if(!isset($fields[$title])) continue;
                    $columnKey[] = $fields[$title];
                }
                if(count($columnKey) == 0) die(js::alert($this->lang->testcase->errorEncode));
            }

            $this->session->set('importFile', $fileName);

            die(js::locate(inlink('showImport', "productID=$productID&branch=$branch"), 'parent.parent'));
        }
        $this->display();
    }

    /**
     * Import case from lib.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  int    $libID
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function importFromLib($productID, $branch = 0, $libID = 0, $orderBy = 'id_desc', $browseType = '', $queryID = 0, $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $browseType = strtolower($browseType);
        $queryID    = (int)$queryID;

        if($_POST)
        {
            $this->testcase->importFromLib($productID);
            die(js::reload('parent'));
        }

        $this->testcase->setMenu($this->products, $productID, $branch);

        $libraries = $this->loadModel('caselib')->getLibraries();
        if(empty($libraries))
        {
            echo js::alert($this->lang->testcase->noLibrary);
            die(js::locate(inlink('browse')));
        }
        if(empty($libID) or !isset($libraries[$libID])) $libID = key($libraries);

        /* Build the search form. */
        $actionURL = $this->createLink('testcase', 'importFromLib', "productID=$productID&branch=$branch&libID=$libID&orderBy=$orderBy&browseType=bySearch&queryID=myQueryID");
        $this->config->testcase->search['module']    = 'testsuite';
        $this->config->testcase->search['onMenuBar'] = 'no';
        $this->config->testcase->search['actionURL'] = $actionURL;
        $this->config->testcase->search['queryID']   = $queryID;
        $this->config->testcase->search['fields']['lib'] = $this->lang->testcase->lib;
        $this->config->testcase->search['params']['lib'] = array('operator' => '=', 'control' => 'select', 'values' => array('' => '', $libID => $libraries[$libID], 'all' => $this->lang->caselib->all));
        $this->config->testcase->search['params']['module']['values']  = $this->loadModel('tree')->getOptionMenu($libID, $viewType = 'caselib');
        if(!$this->config->testcase->needReview) unset($this->config->testcase->search['params']['status']['values']['wait']);
        unset($this->config->testcase->search['fields']['product']);
        unset($this->config->testcase->search['fields']['branch']);
        $this->loadModel('search')->setSearchParams($this->config->testcase->search);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        $this->view->title      = $this->lang->testcase->common . $this->lang->colon . $this->lang->testcase->importFromLib;
        $this->view->position[] = $this->lang->testcase->importFromLib;

        $this->view->libraries  = $libraries;
        $this->view->libID      = $libID;
        $this->view->productID  = $productID;
        $this->view->branch     = $branch;
        $this->view->cases      = $this->loadModel('testsuite')->getNotImportedCases($productID, $libID, $orderBy, $pager, $browseType, $queryID);
        $this->view->modules    = $this->loadModel('tree')->getOptionMenu($productID, 'case', 0, $branch);
        $this->view->libModules = $this->tree->getOptionMenu($libID, 'caselib');
        $this->view->pager      = $pager;
        $this->view->orderBy    = $orderBy;
        $this->view->branches   = $this->loadModel('branch')->getPairs($productID);
        $this->view->browseType = $browseType;
        $this->view->queryID    = $queryID;

        $this->display();
    }

    /**
     * Show import data
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function showImport($productID, $branch = 0)
    {
        if($_POST)
        {
            $this->testcase->createFromImport($productID, (int)$branch);
            die(js::locate(inlink('browse', "productID=$productID"), 'parent'));
        }

        $this->testcase->setMenu($this->products, $productID, $branch);

        $file       = $this->session->importFile;
        $caseLang   = $this->lang->testcase;
        $caseConfig = $this->config->testcase;
        $modules    = $this->loadModel('tree')->getOptionMenu($productID, 'case', 0, $branch);
        $stories    = $this->loadModel('story')->getProductStoryPairs($productID, $branch);
        $fields     = $this->testcase->getImportFields($productID);
        $fields     = array_flip($fields);

        $rows   = $this->loadModel('file')->parseCSV($file);
        $header = array();
        foreach($rows[0] as $i => $rowValue)
        {
            if(empty($rowValue)) break;
            $header[$i] = $rowValue;
        }
        unset($rows[0]);

        foreach($header as $title)
        {
            if(!isset($fields[$title])) continue;
            $columnKey[] = $fields[$title];
        }

        $endField = end($fields);
        $caseData = array();
        $stepData = array();
        $stepVars = 0;
        foreach($rows as $row => $data)
        {
            $case = new stdclass();
            foreach($columnKey as $key => $field)
            {
                if(!isset($data[$key])) continue;
                $cellValue = $data[$key];
                if($field == 'story' or $field == 'module' or $field == 'branch')
                {
                    $case->$field = 0;
                    if(strrpos($cellValue, '(#') !== false)
                    {
                        $id = trim(substr($cellValue, strrpos($cellValue,'(#') + 2), ')');
                        $case->$field = $id;
                    }
                }
                elseif(in_array($field, $caseConfig->export->listFields))
                {
                    if($field == 'stage')
                    {
                        $stages = explode("\n", $cellValue);
                        foreach($stages as $stage) $case->stage[] = array_search($stage, $caseLang->{$field . 'List'});
                        $case->stage = join(',', $case->stage);
                    }
                    else
                    {
                        $case->$field = array_search($cellValue, $caseLang->{$field . 'List'});
                    }
                }
                elseif($field != 'stepDesc' and $field != 'stepExpect')
                {
                    $case->$field = $cellValue;
                }
                else
                {
                    $steps = (array)$cellValue;
                    if(strpos($cellValue, "\n"))
                    {
                        $steps = explode("\n", $cellValue);
                    }
                    elseif(strpos($cellValue, "\r"))
                    {
                        $steps = explode("\r", $cellValue);
                    }

                    $stepKey  = str_replace('step', '', strtolower($field));
                    $caseStep = array();

                    foreach($steps as $step)
                    {
                        $step = trim($step);
                        if(empty($step)) continue;
                        if(preg_match('/^(([0-9]+)\.[0-9]+)([.、]{1})/U', $step, $out))
                        {
                            $num     = $out[1];
                            $parent  = $out[2];
                            $sign    = $out[3];
                            $signbit = $sign == '.' ? 1 : 3;
                            $step    = trim(substr($step, strlen($num) + $signbit));
                            if(!empty($step)) $caseStep[$num]['content'] = $step;
                            $caseStep[$num]['type']    = 'item';
                            $caseStep[$parent]['type'] = 'group';
                        }
                        elseif(preg_match('/^([0-9]+)([.、]{1})/U', $step, $out))
                        {
                            $num     = $out[1];
                            $sign    = $out[2];
                            $signbit = $sign == '.' ? 1 : 3;
                            $step    = trim(substr($step, strpos($step, $sign) + $signbit));
                            if(!empty($step)) $caseStep[$num]['content'] = $step;
                            $caseStep[$num]['type'] = 'step';
                        }
                        elseif(isset($num))
                        {
                            if(!isset($caseStep[$num]['content'])) $caseStep[$num]['content'] = '';
                            $caseStep[$num]['content'] .= "\n" . $step;
                        }
                        else
                        {
                            if($field == 'stepDesc')
                            {
                                $num = 1;
                                $caseStep[$num]['content'] = $step;
                                $caseStep[$num]['type']    = 'step';
                            }
                            if($field == 'stepExpect' and isset($stepData[$row]['desc']))
                            {
                                end($stepData[$row]['desc']);
                                $num = key($stepData[$row]['desc']);
                                $caseStep[$num]['content'] = $step;
                            }
                        }
                    }
                    unset($num);
                    unset($sign);
                    $stepVars += count($caseStep, COUNT_RECURSIVE) - count($caseStep);
                    $stepData[$row][$stepKey] = $caseStep;
                }
            }

            if(empty($case->title)) continue;
            $caseData[$row] = $case;
            unset($case);
        }

        if(empty($caseData))
        {
            echo js::alert($this->lang->error->noData);
            die(js::locate($this->createLink('testcase', 'browse', "productID=$productID&branch=$branch")));
        }

        /* Judge whether the editedTasks is too large and set session. */
        $countInputVars  = count($caseData) * 12 + $stepVars;
        $showSuhosinInfo = common::judgeSuhosinSetting($countInputVars);
        if($showSuhosinInfo) $this->view->suhosinInfo = extension_loaded('suhosin') ? sprintf($this->lang->suhosinInfo, $countInputVars) : sprintf($this->lang->maxVarsInfo, $countInputVars);

        $this->view->title      = $this->lang->testcase->common . $this->lang->colon . $this->lang->testcase->showImport;
        $this->view->position[] = $this->lang->testcase->showImport;

        $this->view->stories   = $stories;
        $this->view->modules   = $modules;
        $this->view->cases     = $this->dao->select('id, module, story, stage, status, pri, type')->from(TABLE_CASE)->where('product')->eq($productID)->andWhere('deleted')->eq(0)->fetchAll('id');
        $this->view->caseData  = $caseData;
        $this->view->stepData  = $stepData;
        $this->view->productID = $productID;
        $this->view->branches  = $this->loadModel('branch')->getPairs($productID);
        $this->view->branch    = $branch;
        $this->view->product   = $this->products[$productID];
        $this->display();
    }

    /**
     * Case bugs.
     *
     * @param  int    $runID
     * @param  int    $caseID
     * @param  int    $version
     * @access public
     * @return void
     */
    public function bugs($runID, $caseID = 0, $version = 0)
    {
        $this->view->title = $this->lang->testcase->bugs;
        $this->view->bugs  = $this->loadModel('bug')->getCaseBugs($runID, $caseID, $version);
        $this->view->users = $this->loadModel('user')->getPairs('noletter');
        $this->display();
    }

    /**
     * Export case getModuleByStory 
     *
     * @params int $storyID
     * @return void
     */
    public function ajaxGetStoryModule($storyID)
    {
        $story = $this->dao->select('module')->from(TABLE_STORY)->where('id')->eq($storyID)->fetch();
        $moduleID = !empty($story) ? $story->module : 0; 
        die(json_encode(array('moduleID'=> $moduleID)));
    }

    /**
     * Get status by ajax.
     *
     * @param  string $methodName
     * @param  int    $caseID
     * @access public
     * @return void
     */
    public function ajaxGetStatus($methodName, $caseID = 0)
    {
        $case   = $this->testcase->getByID($caseID);
        $status = $this->testcase->getStatus($methodName, $case);
        if($methodName == 'update') $status = zget($status, 1, '');
        die($status);
    }
}
