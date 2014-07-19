<?php
namespace Catalog\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Class ShowList
 * @package Catalog\View\Helper
 */
class ShowList extends AbstractHelper
{
    private $newList = array();
    private $tmp     = array();
    private $seoUrlParams  = array();

    /**
     * @param      $list
     * @param      $seoUrl
     * @param null $idParent
     */
    public function __invoke($list, $seoUrl, $idParent = null)
    {
        $this->seoUrlParams = $seoUrl;
        //var_dump($list);
        if (empty($this->newList)) {
            for ($i = 0, $item = count($list); $i < $item; $i++) {
                // Если статус NULL или 3 - показать -> перенес в IndexController
                //if (is_null($list[$i]['idStatus']) || ($list[$i]['idStatus'] == 3)) {
                    if (is_object($list[$i]['idParent'])) {
                        $this->newList[$i] = $list[$i];
                        $this->newList[$i]['idParent'] = (int)$list[$i]['idParent']->getId();
                    } else {
                        $this->newList[$i] = $list[$i];
                    }
                //}
            }

            for ($i = 0, $item = count($this->newList); $i < $item; $i++) {
                if (!in_array($this->newList[$i]['idParent'], $this->tmp)) {

                    $this->tmp[] = $this->newList[$i]['idParent'];
                }
            }
        }
        //var_dump($this->newList);

        echo '<div class="accordion">' . "\n";

        foreach ($this->newList as $elem) {
            if ($elem['idParent'] == $idParent) {
                if (in_array($elem['id'], $this->tmp)) {
                    echo '<h3 class="filled">' . $elem['name'] . '</h3>' . "\n";

                    $this->__invoke($this->newList, $this->seoUrlParams, $elem['id']);
                } else {
                    //$this->seoUrlParams['path'] = 'product/seoUrlC';
                    //$this->seoUrlParams['params']['category'] = $elem['translit'];
                    echo '<a class="empty brand-link" href="' .
                        $this->view->url('product/seoUrlC', array('category' => $elem['translit'])) .'">'. $elem['name']
                        . '</a>';

                    $this->__invoke($this->newList, $this->seoUrlParams, $elem['id']);
                }
            }
        }

        echo '</div>' . "\n";
    }
}