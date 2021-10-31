<?php

require_once('TwitterAPIExchange.php');

class TwitterApi
{
    private $settings;

    function __construct() {
        $this->settings = array(
            'oauth_access_token' => "2426305274-hcclojpGHCDBxNJoX56JBSBwrYJHAxeazdO3gD9",
            'oauth_access_token_secret' => "r6hAux14KZuELW30hAIxr8YC4st0pqolovfjXaY3m1OUZ",
            'consumer_key' => "ZthepyKVgURzyeCXwQIf79KQP",
            'consumer_secret' => "5nkpV2oNCz9CGs7revGIUMvCLnRkupgj9eihp0N5OlLYAakSDU"
        );
    }

    public function getTwitterNameByUserName($user_name) {
        $url = "https://api.twitter.com/2/users/by/username/";

        $requestMethod = "GET";
        $twitter = new TwitterAPIExchange($this->settings);
        $response = $twitter->buildOauth($url.$user_name, $requestMethod)
                            ->performRequest();
         
        return json_decode($response);
    }

    public function getUserIdByUserName($user_name) {
        $user = $this->getTwitterNameByUserName($user_name);
        return $user->data->id;
    }

    public function getLast500TweetsByUserName($user_name) {
        $user_id = $this->getUserIdByUserName($user_name);
        $url = "https://api.twitter.com/2/users/$user_id/tweets";
        $requestMethod = "GET";
        $count = 0;
        $next_token = '';
        $tweet_count = 1;
        $tweets = [];
        do {
            $getfield = "?max_results=100&user.fields=created_at&tweet.fields=created_at" . (!empty($next_token) ? "&pagination_token=$next_token" : '');
            $twitter = new TwitterAPIExchange($this->settings);
            $response_data = $twitter->setGetfield($getfield)
                            ->buildOauth($url, $requestMethod)
                            ->performRequest();
            
            $data = json_decode($response_data);
            foreach ($data->data as $key => $value) {
                $tweet_count++;
                $tweets[date('H', strtotime($value->created_at))][] = $value->id;
            }

            // Break if no token is found to perform next call
            if (!$data->meta || !$data->meta->next_token) {
                break;
            }

            $next_token = $data->meta->next_token;
        } while ($tweet_count < 500);

        return $tweets;
    }

    public function analyseTweetsPerDatesByUsername($user_name) {
        $tweets = $this->getLast500TweetsByUserName($user_name);
        $histogram_arr = [];
        foreach ($tweets as $date => $tweets) {
            $histogram_arr[$date] = count($tweets);
        }

        ksort($histogram_arr);

        return $histogram_arr;
    }
}
 
$twitter_api = new TwitterApi();
var_dump($twitter_api->analyseTweetsPerDatesByUsername('BrainChip_inc'));