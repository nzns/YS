<?php

namespace Route\Web;

use Route\Web;
use Model\Model;

class UserOperate extends Web
{
    public function get()
    {
        if (!$this->user || !$this->user->isAdmin()) {
            $this->alert('Only admin can manage user');
        }

        $user = Model::factory('User')->find_one($this->params['id']);
        if (!$user) {
            $this->alert('User is not exists');
        }

        switch ($this->params['action']) {
            case 'suspend':
                $user->status = -1;
                $user->save();
                break;
            case 'active':
                $user->status = 1;
                $user->save();
                break;
        }

        $this->redirect('/u/' . $user->id);
    }
}