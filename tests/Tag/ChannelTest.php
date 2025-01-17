<?php

namespace Tests\Tag;

use Tests\TestCase;

class ChannelTest extends TestCase
{
    public function test_prev_next_entry()
    {
        $exp = app(\Expressionengine\Coilpack\View\Exp::class);
        $previous = $exp->channel->prev_entry(['channel' => 'blog', 'url_title' => 'action-comedy-how-to']);
        $this->assertEquals('marrow-and-the-broken-bones', $previous->url_title);

        ee()->session->cache = [];

        $next = $exp->channel->next_entry(['channel' => 'blog', 'url_title' => 'marrow-and-the-broken-bones']);
        $this->assertEquals('action-comedy-how-to', $next->url_title);
    }

    public function test_categories()
    {
        $exp = app(\Expressionengine\Coilpack\View\Exp::class);
        $categories = $exp->channel->categories(['channel' => 'blog', 'style' => 'linear']);
        $this->assertEquals([
            'News',
            'Personal',
            'Photos',
            'Videos',
            'Music',
        ], $categories->pluck('cat_name')->toArray());
    }

    public function test_category_heading()
    {
        $exp = app(\Expressionengine\Coilpack\View\Exp::class);
        $heading = $exp->channel->category_heading(['category_id' => '1']);
        $this->assertEquals('News', $heading->category_name);
    }

    public function test_category_archive()
    {
        $exp = app(\Expressionengine\Coilpack\View\Exp::class);
        $categories = $exp->channel->category_archive(['channel' => 'blog', 'style' => 'nested']);
        $this->assertNotEmpty($categories);
    }

//     public function test_info()
//     {
//         $exp = app(\Expressionengine\Coilpack\View\Exp::class);
//         $info = $exp->channel->info(['channel' => 'blog']);
//         // dd($info);
//     }
}
