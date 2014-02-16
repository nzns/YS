<?php

namespace Route\Web\Account;

use Helper\Html;
use Model\Model;
use Route\Web;

class Article extends Web
{
    protected $auth = true;
    protected $tpl = 'account/article.php';
    protected $title = 'My posts';

    public function get()
    {
        if (($page = $this->input->query('page', 1)) < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $user = $this->user;

        if ($this->params && $this->params['id']) {
            $user = Model::factory('User')->find_one($this->params['id']);

            if (!$user) {
                $this->alert("User is not exists");
            }

            $this->title = $user->name . "'s posts";
        }

        $total = $user->articles()->where_gt('status', '-1')->count();

        $this->data['articles'] = $user->articles()->where_gt('status', '-1')->offset($offset)->limit($limit)->order_by_desc('created_at')->find_many();

        $this->data['page'] = Html::makePage($this->input->uri(), 'page=(:num)', $page, $total, $limit);
    }
}
