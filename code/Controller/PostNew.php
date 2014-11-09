<?php

class Controller_PostNew extends Controller_Abstract
{
    public function get()
    {
        if (! $this->_getUsername()) {
            die("You have to login first");
        }

        $minimumVoteCount = $this->_getContainer()->LocalConfig()->getPostingMinimumVotecount();
        if ($this->_getCurrentUser()->getVoteCount() < $minimumVoteCount) {
            die("You have to have $minimumVoteCount vote(s) in order to post");
        }

        $tags = $this->_getContainer()->Tag()->fetchAll();

        echo $this->_getTwig()->render('post_new.html.twig', array(
            'session'       => $this->_getSession(),
            'local_config'  => $this->_getContainer()->LocalConfig(),
            'tags'          => $tags,
        ));
    }

    public function post()
    {
        $imageUrl = isset($_POST['image_url']) ? $_POST['image_url'] : null;
        $subject  = isset($_POST['subject'])   ? $_POST['subject']   : null;
        $body     = isset($_POST['body'])      ? $_POST['body']      : null;
        $tagIds   = isset($_POST['tag_ids'])   ? $_POST['tag_ids']   : null;
        $isActive = isset($_POST['is_active']) ? (int) $_POST['is_active'] : null;

        if ($imageUrl) {
            if (strpos($imageUrl, "javascript:") !== false || strpos($imageUrl, "data:") !== false) {
                die("Looks like an injection attempt");
            }
        }

        if (! $tagIds || empty($tagIds)) {
            die("You have to pick at least one tag");
        }

        $post = $this->_getContainer()->Post()
            ->set('subject', $subject)
            ->set('body', $body)
            ->set('tag_ids', $tagIds)
            ->set('name', isset($profileData['name']) ? $profileData['name'] : null)
            ->set('is_active', $isActive)
            ->set('image_url', $imageUrl)
            ->set('user_id', $this->_getCurrentUser()->getId())
            ->save();

        if (!$isActive) {
            header("Location: " . $post->getEditUrl());
            exit;
        }

        header("Location: " . $post->getUrl());
    }
}