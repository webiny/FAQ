<?php
namespace Apps\Faq\Php\Entities;

use Apps\Core\Php\DevTools\Entity\AbstractEntity;
use Apps\Core\Php\DevTools\Exceptions\AppException;
use Apps\Core\Php\DevTools\WebinyTrait;
use Apps\Core\Php\Entities\User;
use Webiny\Component\Mongo\Index\SingleIndex;

/**
 * Class Article
 *
 * @property string   $id
 * @property string   $questions
 * @property string   $answer
 * @property Category $category
 * @property User     $author
 * @property boolean  $published
 * @property string   $slug
 *
 * @package Apps\TheHub\Php\Entities
 *
 */
class Article extends AbstractEntity
{
    use WebinyTrait;

    protected static $entityCollection = 'FaqArticle';
    protected static $entityMask = '{question}';

    public function __construct()
    {
        parent::__construct();

        $this->index(new SingleIndex('published', 'published'));
        $this->index(new SingleIndex('slug', 'slug'));

        $this->attr('slug')->char()->setToArrayDefault()->setValidators('unique')->setValidationMessages([
            'unique' => 'A category with the same title already exists.'
        ]);

        $this->attr('question')->char()->setRequired(true)->onSet(function ($val) {
            if (!$this->slug && !$this->exists()) {
                $this->slug = $this->str($val)->slug()->val();
            }

            return $val;
        })->setToArrayDefault();
        $this->attr('answer')->object()->setToArrayDefault();
        $this->attr('published')->boolean()->setDefaultValue(false)->setToArrayDefault();


        $category = '\Apps\Faq\Php\Entities\Category';
        $this->attr('category')->many2one()->setEntity($category)->setToArrayDefault();

        $this->api('GET', '/')->setPublic();
        $this->api('GET', '{id}')->setPublic();

        /**
         * @api.name Returns all pages from a category
         */
        $this->api('GET', 'category/{slug}', function ($slug) {
            /* @var Category $category */
            $category = Category::findOne(['slug' => $slug]);

            if ($category) {
                return $this->apiFormatList($category->articles, '*,answer');
            }

            throw new AppException('Category not found.');

        })->setPublic();
    }
}