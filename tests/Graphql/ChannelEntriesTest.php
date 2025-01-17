<?php

namespace Tests\Graphql;

use Tests\TestCase;

class ChannelEntriesTest extends TestCase
{
    public function test_entries()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(channel:"about"){
                    data {
                        entry_id
                        title
                        sticky
                        entry_date(format: "Y-m-d")
                    }
                }
            }
          GQL
        ])
        ->assertJsonFragment(['entry_id' => 2])
        ->assertJsonFragment(['entry_date' => '2022-10-20'])
        ->assertJsonFragment(['title' => 'About Default Theme']);
    }

    public function test_entries_author()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(channel:"about"){
                    data {
                        entry_id
                        author {
                            screen_name
                        }
                    }
                }
            }
          GQL
        ])
        ->assertJsonFragment(['screen_name' => 'admin']);
    }

    public function test_entries_channel()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(channel:"about"){
                    data {
                        entry_id
                        channel {
                            channel_title
                            channel_description
                            channel_id
                        }
                    }
                }
            }
          GQL
        ])
            ->assertJsonFragment(['channel_title' => 'About']);
    }

    public function test_entries_categories()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(channel:"blog"){
                    data {
                        entry_id
                        categories {
                            cat_id
                            cat_name
                            cat_description
                        }
                    }
                }
            }
          GQL
        ])
            ->assertJsonFragment(['cat_name' => 'News']);
    }

    public function test_entries_grid_field()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(channel:"about" limit:1){
                    data {
                        entry_id
                        about_image {
                            image {
                                directory_id
                                width
                                height
                                url
                                path
                                file_size_human_long
                                file_size_human
                            }
                            caption
                            align {
                                value
                            }
                        }
                    }
                }
            }
          GQL
        ])
            ->assertJsonFragment(['url' => url('themes/user/site/default/asset/img/common/common.jpg')])
            ->assertJsonFragment(['caption' => 'Dharmafrog, 2014']);
    }

    public function test_entries_grid_field_image_modifier()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(channel:"about" limit:1){
                    data {
                        entry_id
                        about_image {
                            image(resize: {width:100}) {
                                url
                                width
                                height
                            }
                            caption
                            align {
                                value
                            }
                        }
                    }
                }
            }
          GQL
        ])
        ->assertJsonFragment(['caption' => 'Dharmafrog, 2014'])
        ->assertJsonFragment(['width' => 100]);
    }

    public function test_entries_relationship()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(search: {title:"Test Fieldtypes"} limit:1){
                    data {
                        entry_id
                        test_relationships {
                            title
                            blog_audio {
                                id
                                type {
                                    value
                                }
                            }
                        }
                    }
                }
            }
          GQL
        ])
        ->assertJsonFragment(['title' => 'Entry with SoundCloud audio'])
        ->assertJsonFragment(['id' => '164768245'])
        ->assertJsonFragment(['type' => ['value' => 'soundcloud']]);
    }

    public function test_entries_fluid()
    {
        /**
         * {
                exp_channel_entries(search: {title:"Test Fieldtypes"} limit:1){
                    data {
                        entry_id
                        test_fluid {
                            ... on test_fluid_test_date {
                                __typename
                                value
                            }
                            ... on test_fluid_test_checkboxes {
                                __typename
                                value
                            }
                        }
                    }
                }
            }
         */
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(search: {title:"Test Fieldtypes"} limit:1){
                    data {
                        entry_id
                        title
                        test_fluid {
                            _field_name
                            _field_type
                            test_date
                            test_checkboxes {
                                value
                            }
                        }
                    }
                }
            }
          GQL
        ])
            ->assertJsonFragment(['title' => 'Test Fieldtypes'])
            ->assertJsonFragment(['test_date' => '1664639700'])
            ->assertJsonFragment(['test_checkboxes' => ['value' => 'One']]);
    }

    public function test_entries_text_field_modifier()
    {
        $this->postJson('graphql', [
            'query' => <<<'GQL'
            {
                exp_channel_entries(channel:"about"){
                    data {
                        entry_id
                        title
                        author {
                        screen_name
                        }
                        page_content(length:true)
                    }
                }
            }
          GQL
        ])
            ->assertJsonFragment(['page_content' => '4542']);
    }
}
