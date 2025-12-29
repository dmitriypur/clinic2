<?php
namespace App\Helpers;
use App\Models\Category;

class Categories
{
    public static function getCategories()
    {
        $categories = Container::get('categories');
        if(!$categories){
            $categories = Category::with('pages')->get();
            \App\Helpers\Container::set('categories', $categories);
        }

        return $categories;
    }
}
