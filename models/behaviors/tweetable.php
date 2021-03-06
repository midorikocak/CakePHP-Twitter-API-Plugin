<?php
App::import('Lib', 'Twitter.TwitterUtils');

/**
 * Tweetable Behavior
 *
 * @package twitter
 * @subpackage twitter.models.behaviors
 */
class TweetableBehavior extends ModelBehavior
{

    /**
     * setup
     *
     * @access public
     * @param Object $Model
     * @param array $config
     */
    function setup(&$Model, $config = array())
    {
        App::import('Model', 'Twitter.TwitterStatus');
        $Model->TwitterStatus = ClassRegistry::init('TwitterStatus');
    }

    /**
     * Tweets as an user.
     *
     * @access public
     * @param integer $id
     * @param string $text
     * @return boolean
     */
    function tweet(&$Model, $user, $text, $options = array())
    {
        $defaults = array(
            'shorten' => true,
            'tail' => '...',
        );
        $options = array_merge($defaults, $options);

        if ($options['shorten']) {
            $text = TwitterUtils::shorten($text, $options['tail']);
        }

        if (!is_array($user) || !array_key_exists($Model->alias, $user)) {
            $params = array(
                'conditions' => array(
                    $Model->alias.'.id = ' => $user,
                ),
                'fields' => array(
                    $Model->alias.'.twitter_oauth_token',
                    $Model->alias.'.twitter_oauth_token_secret',
                ),
                'recursive' => -1,
            );
            $user = $Model->find('first', $params);
        }
        $fields = array('oauth_token', 'oauth_token_secret');
        foreach ($fields as $field) {
            $dataField = 'twitter_' . $field;
            if (empty($user[$Model->alias][$dataField])) {
                return false;
            }
            $Model->TwitterStatus->request['auth'][$field] = $user[$Model->alias][$dataField];
        }
        $tweet = array(
            $Model->TwitterStatus->alias => array(
                'text' => $text,
            ),
        );
        if (Configure::read('debug') < 2) {
            return $Model->TwitterStatus->tweet($tweet);
        } else {
            return $Model->log($tweet, LOG_NOTICE);
        }
    }

}
