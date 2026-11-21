<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_page_links_each_post_to_its_id(): void
    {
        Post::create([
            'title' => 'Judul Pertama',
            'excerpt' => 'Ringkasan artikel pertama.',
            'body' => 'Isi lengkap artikel pertama.',
        ]);
        Post::create([
            'title' => 'Judul Kedua',
            'excerpt' => 'Ringkasan artikel kedua.',
            'body' => 'Isi lengkap artikel kedua.',
        ]);

        $response = $this->get('/blog');

        $response->assertOk()
            ->assertSee('/blog-post/1', false)
            ->assertSee('/blog-post/2', false);
    }

    public function test_first_post_link_renders_its_matching_content(): void
    {
        Post::create([
            'title' => 'Judul Pertama',
            'excerpt' => 'Ringkasan artikel pertama.',
            'body' => 'Isi lengkap artikel pertama.',
        ]);

        $response = $this->get('/blog-post/1');

        $response->assertOk()
            ->assertSee('Judul Pertama')
            ->assertSee('Isi lengkap artikel pertama.');
    }

    public function test_second_post_link_renders_its_matching_content(): void
    {
        Post::create([
            'title' => 'Judul Pertama',
            'excerpt' => 'Ringkasan artikel pertama.',
            'body' => 'Isi lengkap artikel pertama.',
        ]);
        Post::create([
            'title' => 'Judul Kedua',
            'excerpt' => 'Ringkasan artikel kedua.',
            'body' => 'Isi lengkap artikel kedua.',
        ]);

        $response = $this->get('/blog-post/2');

        $response->assertOk()
            ->assertSee('Judul Kedua')
            ->assertSee('Isi lengkap artikel kedua.');
    }

    public function test_unknown_post_id_returns_404(): void
    {
        $this->get('/blog-post/999')->assertNotFound();
    }
}
