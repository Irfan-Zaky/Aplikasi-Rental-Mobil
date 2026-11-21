<?php

namespace App\Models;


class post
{
    private static $web_blog =[
        [
            'judul-post' => 'judul pertama',
            'slug' => 'judul-pertama',
            'author' => 'fadhli',
            'blog' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Repudiandae perferendis nostrum placeat illo omnis facere minima repellendus temporibus. Omnis officiis tenetur minus! Eos magni ex dolore odio hic ab cupiditate, totam nemo officiis minus voluptas culpa dolorem cum iusto? Reprehenderit!',
        ],
        [
            'judul-post' => 'judul kedua',
            'slug' => 'judul-kedua',
            'author' => 'irfan',
            'blog' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Repudiandae perferendis nostrum placeat illo omnis facere minima repellendus temporibus. Omnis officiis tenetur minus! Eos magni ex dolore odio hic ab cupiditate, totam nemo officiis minus voluptas culpa dolorem cum iusto? Reprehenderit!',
        ],
    ];

    public static function all() {
        return self::$web_blog;
    }
}
