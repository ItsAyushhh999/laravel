<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class JsonPlaceholderService
{
    protected $baseUrl = 'https://jsonplaceholder.typicode.com';

    public function getPosts()
    {
        $response = Http::get("{$this->baseUrl}/posts");

        return $response->json();
    }

    public function getPost($id)
    {
        $response = Http::get("{$this->baseUrl}/posts/{$id}");

        return $response->json();
    }

    public function createPost($data)
    {
        $response = Http::post("{$this->baseUrl}/posts", $data);

        return $response->json();
    }

    public function getManyPosts(array $ids): array
    {
        $responses = Http::pool(function ($pool) use ($ids) {
            foreach ($ids as $id) {
                $pool->as("post_{$id}")->get("{$this->baseUrl}/posts/{$id}");
            }
        });

        $posts = [];
        foreach ($ids as $id) {
            $key = "post_{$id}";
            if ($responses[$key]->successful()) {
                $posts[] = $responses[$key]->json();
            }
        }

        return $posts;
    }

    public function createManyPosts(array $posts): array
    {
        $responses = Http::pool(function ($pool) use ($posts) {
            foreach ($posts as $index => $post) {
                $pool->as("post_{$index}")->post("{$this->baseUrl}/posts", $post);
            }
        });

        $created = [];
        foreach (array_keys($posts) as $index) {
            $key = "post_{$index}";
            if ($responses[$key]->successful()) {
                $created[] = $responses[$key]->json();
            }
        }

        return $created;
    }
}
