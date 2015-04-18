<?php
/**
 * Class Services_TwitterNotify
 */
class Services_TwitterNotify implements Services_NotifyInterface
{
    /**
     * @var Model_LocalConfig
     */
    protected $localConfig;

    public function __construct(Model_LocalConfig $config)
    {
        $this->localConfig = $config;
    }

    /**
     * @param Model_User $to
     * @param Model_User $from
     * @param string     $message
     *
     * @return bool|string
     */
    public function send($to, $from, $message = "")
    {
        if ($message == "") {
            // Construct Tweet
            $message = "@" . $to->getTwitterUsername() . " you were upvoted by @" . $from->getTwitterUsername() .
                       " on magehero.com/" . $to->getGithubUsername();
        }

        $settings = array(
            'oauth_access_token' => $this->localConfig->get('twitter_oauth_access_token'),
            'oauth_access_token_secret' => $this->localConfig->get('twitter_oauth_access_token_secret'),
            'consumer_key' => $this->localConfig->get('twitter_consumer_api_key'),
            'consumer_secret' => $this->localConfig->get('twitter_consumer_api_secret')
        );
        $url = 'https://api.twitter.com/1.1/statuses/update.json';
        $requestMethod = 'POST';
        $postfields = array("status" => $message);
        try{
            $twitter = new TwitterAPIExchange($settings);
            $response = $twitter
                ->buildOauth($url, $requestMethod)
                ->setPostfields($postfields)
                ->performRequest();
            // Error handling for tweet failurs , is not required. I am pretty sure that the voters are not interested
            // in knowing if the tweet was posted or now. 
            return $response;
        }catch(Exception $e) {
            return false;
        }
        //var_dump(json_decode($response));die;
    }
}