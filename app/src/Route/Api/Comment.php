<?php

namespace Route\Api;

use Model\Article;
use Pagon\Html;
use Route\Api;
use Model\Model;
use ORM;

class Comment extends Api
{
    public function get($req, $res)
    {
        $page = max(1, (int)$req->query('page', 1));
        $count = $req->query('count', 20);
        $pid = $req->query('pid');

        if (!$pid) {
            $this->error('Params "pid" is needed');
        }

        $comments = Model::factory('Comment')->where('article_id', $pid)
            ->limit($count)
            ->offset(($page - 1) * $count)
            ->order_by_desc('created_at')
            ->find_many();

        if (!$req->query('html')) {
            $this->data['data'] = array_map(function ($item) {
                return $item->as_array();
            }, $comments);
        } else {
            $app = $this->app;
            $html = '';
            foreach ($comments as $comment) {
                $html .= $app->compile('blocks/comment.php', array(
                        'comment' => $comment,
                        'user'    => $this->user)
                )->render();
            }
            $this->data['html'] = $html;
        }
    }

    public function post($req, $res)
    {
        $text = $req->data('text');
        $article_id = $req->data('article_id');

        if ($this->user->isUnVerified()) {
            $this->error('Your account is not active now, plz check your mail and active.');
        }

        if (!$article = Model::factory('Article')
            ->find_one($article_id)
        ) {
            $this->error('Article is not exists');
        }

        $comment = Model::factory('Comment')->create(array(
            'article_id' => $article_id,
            'text'       => $text,
            'user_id'    => $this->user->id,
        ));

        try {
            ORM::get_db()->beginTransaction();

            if (!$comment->save()) {
                $this->error('Comment create error');
            }

            $article->set_expr('comments_count', '`comments_count` + 1');
            $article->save();

            ORM::get_db()->commit();
        } catch (\PDOException $e) {
            ORM::get_db()->rollBack();
            // @TODO Logging
            $this->error('Comment error because of the bad database');
        }

        $this->app->emit('comment', $comment);

        $this->data['message'] = 'Comment ok';

        if ($req->data('html')) {
            $this->data['html'] = $this->app->compile('blocks/comment.php', array('comment' => $comment, 'user' => $this->user))->render();
        } else {
            $this->data['data'] = $comment->as_array();
        }
    }

    public function delete()
    {
        $this->auth();
        $comment = Model::factory('Comment')->find_one($this->params['id']);

        if (!$comment) {
            $this->error('Comment not exists');
        }

        $article = Model::factory('Article')->find_one($comment->article_id);

        try {
            ORM::get_db()->beginTransaction();

            if (!$comment->delete()) {
                $this->error('Comment delete fail');
            }

            if ($article) {
                $article->set_expr('comments_count', '`comments_count` + 1');
                $article->save();
            }
            ORM::get_db()->commit();
        } catch (\PDOException $e) {
            ORM::get_db()->rollBack();
            $this->error('Comment delete error');
        }

        $this->ok('Comment delete ok');
    }
}