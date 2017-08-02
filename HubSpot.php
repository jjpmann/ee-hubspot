<?php

namespace jjpmann\EE;

use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\Contacts;
use SevenShores\Hubspot\Resources\BlogPosts;
use SevenShores\Hubspot\Resources\BlogTopics;

class HubSpot {


    protected $client;

    protected $response;

    protected $blogs;

    protected $topics;

    public function __construct($key = false)
    {
        $key = env('HUBSPOT_API_KEY') ?: $key;
        try {
            $this->client = new Client(['key' => $key]);
        } catch (Exception $e) {
            die('bad key');
        }
        
    }

    public function topics()
    {
        $BlogTopics = new BlogTopics($this->client);
        $cacheKey = '/hubpost/topics/' . date("Y-m-d");

        $topics = []; //ee()->cache->get($cacheKey) ?: [];

        if ( empty($topics) ) {
            $response = $this->topicCache();
            //$response = $this->getResponse($BlogTopics->all(['limit' => 999]))->objects;

            foreach ($response as $topic) {
                // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $topic ); exit;

                $topics[] = [
                    'id'            => $topic->id,
                    'name'          => $topic->name,
                    'slug'          => $topic->slug,
                    'description'   => $topic->description,
                ];
            }
            ee()->cache->save($cacheKey, $topics, 60*60*24);  // 24 hours
        }

        return $topics;

    }

    public function blogs()
    {
        $blogs = new BlogPosts($this->client);
        $cacheKey = '/hubpost/blogs-all/' . date("Y-m-d");

        $cacheKey = '/hubpost/blogs/' . date("Y-m-d");

        $posts = []; //ee()->cache->get($cacheKey) ?: [];
        
        if ( empty($posts) ) {

            $response = $this->blogCache(); //$blogs->all(['limit' => 999]);

            foreach ($response as $post) {
                // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $post ); exit;
                
                $this->getWidgets($post);

                $posts[] = [
                    'id'            => $post->id,
                    'title'         => $post->title,
                    'link'          => $post->absolute_url,
                    'image'         => $post->featured_image,
                    'topics'        => $post->topic_ids,
                    'listing_bg'    => $post->listing_bg,
                    'listing_image' => $post->listing_image,
                    'status'        => $post->state,
                    'date'          => $post->publish_date,
                ];
            }

            ee()->cache->save($cacheKey, $posts, 60*60*24);  // 24 hours
        }

        return collect($posts);
    }

    protected function getWidgets(&$post)
    {
        $widgets = [
            'listing_image' => [
                'name'  => 'module_150124817236522',
                'src'   => 'src'
            ],
            'listing_bg'    => [
                'name'  => 'module_150124856292331',
                'src'   => 'value'
            ]
        ];

        foreach ($widgets as $name => $widget) {
            $post->$name = '';

            if (isset($post->widgets->{$widget['name']})) {
                 $post->$name = $post->widgets->{$widget['name']}->body->{$widget['src']};
            }
        }
    }

    public function _topics()
    {
        $blogs = new BlogPosts($this->client);
        $cacheKey = '/hubpost/topics/' . date("Y-m-d");

        $topics = []; //ee()->cache->get($cacheKey) ?: [];

        if ( empty($topics) ) {
            
            $response = $this->blogCache(); //$blogs->all(['limit' => 999]);

            foreach ($response as $post) {
                // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $post ); exit;

                $topics = array_merge($topics, $post->topic_ids);
            }

            $topics = array_values(array_unique($topics));

            ee()->cache->save($cacheKey, $topics, 60*60*24);  // 24 hours
        }

        return collect($topics);
    }




    protected function blogCache()
    {
        $cacheKey = '/hubpost/blogs-all/' . date("Y-m-d");

        $data = ee()->cache->get($cacheKey) ?: [];

        if ( empty($data) ) {

            $data = $this->getResponse(
                (new BlogPosts($this->client))->all(['limit' => 999])
            )->objects;
            
            ee()->cache->save($cacheKey, $data, 60*60*24);  // 24 hours
        }

        return $data;
    }

    protected function topicCache()
    {
        $cacheKey = '/hubpost/topics-all/' . date("Y-m-d");

        $data = ee()->cache->get($cacheKey) ?: [];

        if ( empty($data) ) {

            $data = $this->getResponse(
                    (new BlogTopics($this->client))->all(['limit' => 999])
            )->objects;

            ee()->cache->save($cacheKey, $data, 60*60*24);  // 24 hours
        }

        return $data;
    }

    protected function getResponse($resp)
    {
        $this->response = $resp;
        if (!$resp->data) {
            return $this->nullResponse();
        }
        return $resp->data;
    }

    protected function nullResponse()
    {
        $resp = new stdClass();
        $resp->limit    = 0;
        $resp->offset   = 0;
        $resp->total    = 0;
        $resp->total_count = 0;
        $resp->data     = [];
        return $resp; 
    }

}




// // $contacts = new Contacts($client);

// $topics = new BlogTopics($client);

// $response = $topics->all(['limit'=>500]);

// // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $response->data ); exit;

// foreach ($response->data->objects as $topic) {
//     $cats[] = $topic->name;
// }

// sort($cats);

// echo implode('<br>', $cats);

// exit;

// public 'limit' => int 300
// public 'offset' => int 0
// public 'total' => int 119
// public 'total_count' => int 119
// public 'data' => array 119






// $response = $blogs->getById(5239794142);



// echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $response->data ); exit;