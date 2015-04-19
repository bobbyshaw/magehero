<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Commands_EmailUpdatesCommand extends Command {

    protected $_container;

    protected function configure()
    {
        $this->setName("emails:send")
            ->setDescription("Send email updates");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $recentPosts = $this->_getContainer()->Post()->fetchByWeek(1);
        $recentPosts = array_splice($recentPosts, 0, 3);

        $loader = new Twig_Loader_Filesystem(dirname(dirname(dirname(__FILE__))) . '/template');
        $twig = new Twig_Environment($loader);

        $body = $twig->render('email/popular_posts.html.twig', array(
            'posts'   => $recentPosts
        ));
        foreach($this->_getContainer()->User()->fetchAll() as $user) {
            $user = $this->_getContainer()->User()->setData($user);
            mail($user->getEmail(), "This Week on MageHero: ", $body, "From: updates@magehero.com");
        }
    }

    protected function _getContainer()
    {
        if (isset($this->_container)) {
            return $this->_container;
        }

        $container = new Model_Container();

        $this->_container = $container;
        return $this->_container;
    }
}