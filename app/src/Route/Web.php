<?php

namespace Route;

use Pagon\Route\Rest;
use Pagon\View;
use Model\Model;
use Pagon\Url;

class Web extends Rest
{
    /**
     * @var string Template to render
     */
    protected $tpl;

    /**
     * @var string Layout template to render
     */
    protected $layout = 'layout.php';

    /**
     * @var array The data to use for template
     */
    protected $data = array();

    /**
     * @var \Model\User Login user
     */
    protected $user;

    /**
     * @var bool The page need auth
     */
    protected $auth = false;

    /**
     * @var string Title of site
     */
    protected $title = 'Home';


    /**
     * @var bool Is robot or spider?
     */
    protected $is_robot = false;

    /**
     * Before logic
     */
    protected function before()
    {
        $this->loadOrm();

        $user_id = $this->input->session('login');

        if ($user_id && ($this->user = Model::factory('User')->find_one($user_id))) {
            $this->output->cookie('nick', $this->user->name);
        }

        if ($this->auth) {
            $this->auth();
        }

        // 检查搜索爬虫
        if (preg_match('/bot|spider/i', $this->input->userAgent())) {
            $this->is_robot = true;
        }

        $this->data['user'] = $this->user;
        $this->data['id'] = strtolower(substr(get_called_class(), strlen('Route\Web') + 1));
        $this->data['title'] = & $this->title;
        $this->data['json'] = array(
            'robot' => $this->is_robot,
            'site_prefix' => Url::to('', null, true)
        );
    }

    /**
     * Auth
     */
    protected function auth()
    {
        if (!$this->user) {
            $this->redirect(
                '/account/login?continue=' .
                ($this->input->is('get') ? $this->input->uri() : $this->input->refer())
            );
        }
    }

    /**
     * Load ORM and database
     */
    protected function loadOrm()
    {
        $this->app->loadOrm();
    }

    /**
     * Alert message
     *
     * @param $message
     */
    protected function alert($message)
    {
        $body = new View(
            'alert.php',
            array('message' => $message),
            array('dir' => $this->app->views)
        );

        $this->app->render($this->layout, array('body' => $body) + $this->data);
        $this->output->end();
    }

    /**
     * Redirect the page
     *
     * @param $uri
     */
    protected function redirect($uri)
    {
        $this->output->redirect(Url::to($uri))->end();
    }

    /**
     * After logic
     */
    protected function after()
    {
        $body = new View(
            $this->tpl,
            $this->data + $this->app->locals,
            array('dir' => $this->app->views)
        );

        $this->app->render($this->layout, array('body' => $body->render()) + $this->data);
    }

    /**
     * Compile
     *
     * @param string $tpl
     * @param array  $data
     * @return View
     */
    public function compile($tpl, array $data = array())
    {
        return new View(
            $tpl,
            $data + $this->app->locals,
            array('dir' => $this->app->views)
        );
    }
}