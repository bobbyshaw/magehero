<?php

class Model_User extends Model_Record
{
    protected $_data;
    protected $_lastPost;

    protected function _getTable() 
    {
        return 'users'; 
    }
    protected function _getTableIdFieldname() 
    {
        return 'user_id'; 
    }
    protected function _getColumns()
    {
        return array('is_active', 'username', 'name', 'details_json');
    }

    /**
     * @var Model_LocalConfig
     */
    protected $_localConfig;

    /**
     * @param Model_LocalConfig $config
     */
    public function __construct(Model_LocalConfig $config)
    {
        $this->_localConfig = $config;
    }

    /**
     * @param $username
     *
     * @return $this
     */
    public function loadByUsername($username)
    {
        $query = $this->_localConfig->database()->select()
            ->from("users")
            ->joinLeft(
                'user_vote',
                'user_vote.elected_user_id = users.user_id',
                array(
                    'COUNT(user_vote.user_vote_id) as vote_count'
                )
            )
            ->joinLeft(
                array('voting_user' => 'users'),
                'voting_user.user_id = user_vote.voting_user_id',
                array(
                    'GROUP_CONCAT(voting_user.name) as voting_users'
                )
            )
            ->group('users.user_id')
            ->where('users.is_active = 1')
            ->where("users.username = ?", $username);

        $this->_data = $this->_localConfig->database()->fetchRow($query);
        return $this;
    }

    /**
     * @param $electedUserId
     *
     * @return string
     */
    public function hasVotedFor($electedUserId)
    {
        $query = $this->_localConfig->database()->select()
            ->from("user_vote")
            ->where("voting_user_id = ?", $this->getId())
            ->where("elected_user_id = ?", $electedUserId);

        return $this->_localConfig->database()->fetchOne($query);
    }

    /**
     * @param $postId
     *
     * @return string
     */
    public function hasVotedForPost($postId)
    {
        $query = $this->_localConfig->database()->select()
            ->from("post_vote")
            ->where("voting_user_id = ?", $this->getId())
            ->where("post_id = ?", $postId);

        return $this->_localConfig->database()->fetchOne($query);
    }

    /**
     * @param $votingUserId
     *
     * @return $this
     */
    public function addVoteFrom($votingUserId)
    {
        $this->_localConfig->database()->insert(
            'user_vote', array(
            'voting_user_id'    => $votingUserId,
            'elected_user_id'   => $this->getId(),
            'created_at'        => \Carbon\Carbon::now()->toDateTimeString(),
            )
        );

        $data = array(
            'updated_at'    => \Carbon\Carbon::now()->toDateTimeString(),
        );
        $this->_localConfig->database()->update('users', $data, 'user_id = ' . $this->getId());

        return $this;
    }

    /**
     * @param $votingUserId
     *
     * @return $this
     */
    public function removeVoteFrom($votingUserId)
    {
        $this->_localConfig->database()->delete(
            'user_vote',
            "voting_user_id = $votingUserId AND elected_user_id = " . $this->getId()
        );

        return $this;
    }

    /**
     * @param $postId
     *
     * @return $this
     */
    public function removeVoteFromPost($postId)
    {
        $this->_localConfig->database()->delete(
            'post_vote',
            "voting_user_id = " . $this->getId() . " AND post_id = " . $postId
        );

        return $this;
    }

    /**
     * @param $postId
     *
     * @return $this
     */
    public function addVoteToPost($postId)
    {
        $this->_localConfig->database()->insert(
            'post_vote', array(
            'voting_user_id'    => $this->getId(),
            'post_id'           => $postId,
            'created_at'        => \Carbon\Carbon::now()->toDateTimeString(),
            )
        );

        return $this;
    }

    /**
     * @param $userId
     *
     * @return $this
     */
    public function load($userId)
    {
        $query = $this->_localConfig->database()->select()
            ->from("users")
            ->joinLeft(
                'user_vote',
                'user_vote.elected_user_id = users.user_id',
                array(
                    'COUNT(user_vote.user_vote_id) as vote_count'
                )
            )
            ->joinLeft(
                array('voting_user' => 'users'),
                'voting_user.user_id = user_vote.voting_user_id',
                array(
                    'GROUP_CONCAT(voting_user.name) as voting_users'
                )
            )
            ->group('users.user_id')
            ->where('users.is_active = 1')
            ->where("users.user_id = ?", $userId);

        $this->_data = $this->_localConfig->database()->fetchRow($query);
        return $this;
    }

    /**
     * @return Zend_Db_Select
     */
    public function selectAll()
    {
        $postsQuery = $this->_localConfig->database()->select()
            ->from(
                'posts', array(
                'user_id',
                'is_active',
                'MAX(posts.post_id) AS post_id'
                )
            )
            ->where('posts.is_active = 1')
            ->order('posts.post_id DESC')
            ->group('posts.user_id');

        $query = $this->_localConfig->database()->select()
            ->from("users")
            ->joinLeft(
                'user_vote',
                'user_vote.elected_user_id = users.user_id',
                array(
                    'COUNT(user_vote.user_vote_id) as vote_count',
                )
            )
            ->joinLeft(
                array('voting_user' => 'users'),
                'voting_user.user_id = user_vote.voting_user_id',
                array(
                    'GROUP_CONCAT(voting_user.name) as voting_users'
                )
            )
            ->joinLeft(
                array('posts' => $postsQuery),
                'posts.user_id = users.user_id AND posts.is_active = 1',
                array()
            )
            ->where('users.is_active = 1')
            ->group('users.user_id')
            ->order(array('posts.post_id DESC', 'users.updated_at DESC'));

        return $query;
    }

    /**
     * @return null
     */
    public function getName() 
    {
        return $this->get('name'); 
    }

    /**
     * @return null
     */
    public function getCreatedAt() 
    {
        return $this->get('created_at'); 
    }

    /**
     * @return null
     */
    public function getUpdatedAt() 
    {
        return $this->get('updated_at'); 
    }

    /**
     * @return null
     */
    public function getEmail() 
    {
        return $this->getDetail('email'); 
    }

    /**
     * @return null
     */
    public function getVoteCount() 
    {
        return $this->get('vote_count'); 
    }

    /**
     * @return null
     */
    public function getUsername() 
    {
        return $this->get('username'); 
    }

    /**
     * @return null
     */
    public function getVotingUsernames() 
    {
        return $this->get('voting_users'); 
    }

    /**
     * @return null
     */
    public function getImageUrl() 
    {
        return $this->getDetail('image_url'); 
    }

    /**
     * @return null
     */
    public function getNextAvailable() 
    {
        return $this->getDetail('next_available'); 
    }

    /**
     * @return null
     */
    public function certificationBoardUrl() 
    {
        return $this->getDetail('certification_board_url'); 
    }

    /**
     * @return null
     */
    public function getCertifiedDeveloperUrl() 
    {
        return $this->getDetail('certified_developer_url'); 
    }

    /**
     * @return null
     */
    public function certifiedDeveloperPlusUrl() 
    {
        return $this->getDetail('certified_developer_plus_url'); 
    }

    /**
     * @return null
     */
    public function certifiedSolutionSpecialistUrl() 
    {
        return $this->getDetail('certified_solution_specialist_url'); 
    }

    /**
     * @return null
     */
    public function certifiedFrontendDeveloperUrl() 
    {
        return $this->getDetail('certified_frontend_developer_url');
    }

    /**
     * @return null
     */
    public function stackoverflowUrl() 
    {
        return $this->getDetail('stackoverflow_url'); 
    }

    /**
     * @return null
     */
    public function linkedinUrl() 
    {
        return $this->getDetail('linkedin_url'); 
    }

    /**
     * @return string
     */
    public function getGithubUsername() 
    {
        return (string) $this->getDetail('github_username'); 
    }

    /**
     * @return null
     */
    public function getTwitterUsername() 
    {
        return $this->getDetail('twitter_username'); 
    }

    /**
     * @return null
     */
    public function getWebsiteUrl() 
    {
        return $this->getDetail('url_website'); 
    }

    /**
     * @return null
     */
    public function getCompany() 
    {
        return $this->getDetail('company'); 
    }

    /**
     * @return null
     */
    public function getAboutYou() 
    {
        return $this->getDetail('about_you'); 
    }

    /**
     * @return float
     */
    public function getLatitude() 
    {
        return (float)$this->getDetail('latitude'); 
    }

    /**
     * @return float
     */
    public function getLongitude() 
    {
        return (float)$this->getDetail('longitude'); 
    }

    /**
     * @return null|string
     */
    public function getNextAvailableFriendly()
    {
        try {
            $dt = \Carbon\Carbon::parse($this->getDetail('next_available'));
            if ($dt->lt(\Carbon\Carbon::now())) {
                return "Available";
            } else {
                return $dt->diffForHumans();
            }
        } catch (Exception $e) {
            return $this->getDetail('next_available');
        }
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function getDetail($key)
    {
        $detailJson = $this->get('details_json');
        $detailsArray = json_decode($detailJson, true);
        if (! $detailsArray) {
            return null;
        }

        return isset($detailsArray[$key]) ? $detailsArray[$key] : null;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        $parts = array();
        if ($this->getDetail('city')) {
            $parts[] = $this->getDetail('city');
        }

        if ($this->getDetail('state')) {
            $parts[] = $this->getDetail('state');
        }

        if ($this->getDetail('country')) {
            $parts[] = $this->getDetail('country');
        }

        return implode(", ", $parts);
    }

    /**
     * @return $this
     *
     * @TODO: Make this more dry by using a beforecreate hook or something
     */
    public function create()
    {
        foreach ($this->_getColumns() as $column) {
            $data[$column] = $this->get($column);
        }

        $data['updated_at'] = \Carbon\Carbon::now()->toDateTimeString();
        $data['is_active'] = true;
        $this->_localConfig->database()->insert($this->_getTable(), $data);

        return $this;
    }

    /**
     * @return string
     */
    public function fetchPostCount()
    {
        $query = $this->_localConfig->database()->select()
            ->from(
                'posts', array(
                'post_count' => 'COUNT(*)'
                )
            )
            ->where('posts.is_active = 1')
            ->where('user_id = ?', $this->getId());

        $postCount = $this->_localConfig->database()->fetchOne($query);
        return $postCount;
    }

    /**
     * @return $this|null
     */
    public function getLastPost()
    {
        if (isset($this->_lastPost)) {
            return $this->_lastPost;
        }

        $query = $this->_localConfig->database()->select()
            ->from('posts')
            ->where('user_id = ?', $this->getId())
            ->where('posts.is_active = 1')
            ->order('posts.post_id DESC');

        $row = $this->_localConfig->database()->fetchRow($query);
        if (! $row) {
            return null;
        }

        $postModel = $this->_getContainer()->Post()->setData($row);

        return $postModel;
    }

    /**
     * @return string
     */
    public function getUrl() 
    {
        $url = implode(
            "/", array($this->_localConfig->get('base_url'),
             $this->getGithubUsername(), 'posts')
        );
        return $url;
    }
}
