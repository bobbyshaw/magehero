<?php

class Controller_PostList extends Controller_Abstract
{
    public function get()
    {
        $postsThisWeek = $this->_getContainer()->Post()->fetchByWeek(1);
        $postsLastWeek = $this->_getContainer()->Post()->fetchByWeek(2, 1);

        echo $this->_getTwig()->render('post_list.html.twig', array(
            'session'           => $this->_getSession(),
            'posts_this_week'   => $postsThisWeek,
            'posts_last_week'   => $postsLastWeek,
            'local_config'      => $this->_getContainer()->LocalConfig(),
        ));
    }


}