<?php

namespace App\Providers;

use App\Models\ArticleTag;
use App\Models\Chat;
use App\Observers\ArticleObserver;
use App\Observers\ArticleTagObserver;
use App\Observers\CategoryObserver;
use App\Observers\ChatObserver;
use App\Observers\CommentObserver;
use App\Observers\ConfigObserver;
use App\Observers\FriendshipLinkObserver;
use App\Observers\TagObserver;
use Cache;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Config;
use App\Models\FriendshipLink;
use App\Models\Tag;
use App\Models\Category;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //分配前台通用的数据
        view()->composer('home/*', function($view){
            $assign = Cache::remember('common', 10080, function () {

                // 获取分类导航
                $category = Category::select('id', 'name')->get();

                // 获取标签下的文章数统计
                $tagModel = new Tag();
                $tag = $tagModel->getArticleCount();

                // 获取置顶推荐文章
                $topArticle = Article::select('id', 'title')
                    ->where('is_top', 1)
                    ->orderBy('created_at', 'desc')
                    ->get();

                // 获取最新评论
                $commentModel = new Comment();
                $newComment = $commentModel->getNewData();

                // 获取友情链接
                $friendshipLink = FriendshipLink::select('name', 'url')->orderBy('sort')->get();

                $data = [
                    'category' => $category,
                    'tag' => $tag,
                    'topArticle' => $topArticle,
                    'newComment' => $newComment,
                    'friendshipLink' => $friendshipLink
                ];
                return $data;
            });

            $view->with($assign);
        });

        // 分配全站通用的数据
        view()->composer('*', function ($view) {
            // 获取配置项
            $config = Cache::remember('config', 10080, function () {
                return Config::pluck('value','name');
            });
            $assign = [
                'config' => $config
            ];
            $view->with($assign);
        });

        // 注册观察者
        Article::observe(ArticleObserver::class);
        ArticleTag::observe(ArticleTagObserver::class);
        Category::observe(CategoryObserver::class);
        Chat::observe(ChatObserver::class);
        Comment::observe(CommentObserver::class);
        Config::observe(ConfigObserver::class);
        FriendshipLink::observe(FriendshipLinkObserver::class);
        Tag::observe(TagObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 逆向迁移
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Way\Generators\GeneratorsServiceProvider::class);
            $this->app->register(\Xethron\MigrationsGenerator\MigrationsGeneratorServiceProvider::class);
            // laravel-ide-helper ide支持
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }
}
