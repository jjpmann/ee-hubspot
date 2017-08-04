<?php

namespace jjpmann\EE;

use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\Blogs;
use SevenShores\Hubspot\Resources\Contacts;
use SevenShores\Hubspot\Resources\BlogPosts;
use SevenShores\Hubspot\Resources\BlogTopics;
use SevenShores\Hubspot\Resources\BlogAuthors;

class HubSpot {


    protected $client;

    protected $response;

    protected $filters = ['limit' => 999];
    
    public function __construct($key = false)
    {
        $key = env('HUBSPOT_API_KEY') ?: $key;
        try {
            $this->client = new Client(['key' => $key]);
        } catch (Exception $e) {
            die('bad key');
        }
        
    }


    public function statuses()
    {
        return [
            ''          => '-- All --',
            'DRAFT'     => 'Draft',
            'PUBLISHED' => 'Published'
        ];
    }

    public function topics()
    {
        $cacheKey = '/hubpost/topics/' . date("Y-m-d");
        $topics = []; //ee()->cache->get($cacheKey) ?: [];

        if ( empty($topics) ) {
            $response = $this->topicCache();

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

        return collect($topics);

    }

    public function authors()
    {
        $cacheKey = '/hubpost/authors/' . date("Y-m-d");
        $authors = []; //ee()->cache->get($cacheKey) ?: [];

        if ( empty($authors) ) {
            $response = $this->authorCache();

            foreach ($response as $author) {
                //echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $author ); exit;
    
                $authors[] = [
                    'id'            => $author->id,
                    'name'          => $author->displayName,
                    'slug'          => $author->slug,
                    'image'         => $author->avatar,
                    'bio'           => $author->bio,
                ];
            }
            ee()->cache->save($cacheKey, $authors, 60*60*24);  // 24 hours
        }

        return collect($authors);

    }

    public function posts($filters = [])
    {   
        
        $cacheKey = '/hubpost/posts/' . date("Y-m-d");

        $posts = []; //ee()->cache->get($cacheKey) ?: [];
        
        if ( empty($posts) ) {
            $response = $this->postCache(); 

            foreach ($response as $post) {

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
                    'author'        => isset($post->blog_author_id) ? $post->blog_author_id : 0,
                    'blog'          => $post->content_group_id
                ];
            }

            ee()->cache->save($cacheKey, $posts, 60*60*24);  // 24 hours
        }

        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $filters ); exit;
        

        $posts = collect($posts);

        foreach ($filters as $filter => $values) {
            if (!$values || (is_array($values) && $values[0] == '')) {
                continue;
            }
            // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $values ); exit;
            

            $posts = $posts->filter(function($item) use ($filter, $values) {
               
                if (is_array($values)) {
                    foreach ($values as $value) {
                        if (is_array($item[$filter]) && in_array($value, $item[$filter])) {
                            return true;
                        }

                        if ($item[$filter] == $value) {
                            return true;
                        }
                    }
                }
                
                if (is_array($item[$filter]) && in_array($values, $item[$filter])) {
                    return true;
                }

                if ($item[$filter] == $values) {
                    return true;
                }

            });
        }

        return $posts;
    }

    public function blogs()
    {
        $cacheKey = '/hubpost/blogs/' . date("Y-m-d");

        $blogs = []; //ee()->cache->get($cacheKey) ?: [];
        
        if ( empty($blogs) ) {
            $response = $this->getResponse(
                    (new Blogs($this->client))->all()
            )->objects;
            

            foreach ($response as $blog) {
                
                $blogs[] = [
                    'id'            => $blog->id,
                    'name'         => $blog->public_title,
                    'link'          => $blog->absolute_url,
                    'html_title'    => $blog->html_title,
                ];
            }

            ee()->cache->save($cacheKey, $blogs, 60*60*24);  // 24 hours
        }

        return collect($blogs);
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


    protected function authorCache()
    {
        $filters = $this->filters;
        $callback = function() use ($filters) {
            return (new BlogAuthors($this->client))->all($filters);
        };
        return $this->cache('authors-all', $filters, $callback);
    }

    protected function postCache()
    {
        $filters = $this->filters;
        $callback = function() use ($filters) {
            return (new BlogPosts($this->client))->all($filters);
        };
        return $this->cache('posts-all', $filters, $callback);
    }

    protected function topicCache()
    {
        $filters = $this->filters;
        $callback = function() use ($filters) {
            return (new BlogTopics($this->client))->all($filters);
        };
        return $this->cache('topics-all', $filters, $callback);
    }

    protected function cache($type, $filters, Callable $callback, $cacheTime = 60*60*24)
    {
        $cacheKey = '/hubpost/' . $type . '/' . md5(implode('-',$filters) . date("Y-m-d"));
        $data = ee()->cache->get($cacheKey) ?: [];

        if ( empty($data) ) {
            $data = $this->getResponse( $callback() )->objects;
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
