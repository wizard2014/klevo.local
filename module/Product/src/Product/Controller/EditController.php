<?php

namespace Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Form\Annotation\AnnotationBuilder;

use Product\Entity\Product as ProductEntity;
use Product\Model\Product;

use Product\Form;

use GoSession;

class EditController extends AbstractActionController
{
    const PRODUCT_ENTITY  = 'Product\Entity\Product';
    const BRAND_ENTITY    = 'Catalog\Entity\Brand';
    const CATEGORY_ENTITY = 'Catalog\Entity\Catalog';
    const STATUS_ENTITY   = 'Data\Entity\Status';
    const STORE_ENTITY    = 'Data\Entity\Store';

    /**
     * @var
     */
    protected $em;
    protected $fullName;

    /**
     * @return array|\Zend\Http\Response|ViewModel
     */
    public function indexAction()
    {
        $currentSession = new Container();

        // Если редирект на главную-редактирования через кнопку
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost('redirect');

            if (isset($data)) {
                unset($currentSession->idBrand);
                unset($currentSession->idCatalog);

                return $this->prg('/edit-product', true);
            }
        }

        $externalCall = $this->params('externalCall', false);

        // Получение queryString параметров (array)
        $param = $this->params()->fromQuery();

        // Сохранение/подстановка параметров запроса в/из сессии
        if (isset($currentSession->idBrand) && !isset($param['brand'])) {
            $param['brand'] = $currentSession->idBrand;
        } elseif (isset($param['brand'])) {
            $currentSession->idBrand = $param['brand'];
        }

        if (isset($currentSession->idCatalog) && !isset($param['catalog'])) {
            $param['catalog'] = $currentSession->idCatalog;
        } elseif (isset($param['catalog'])) {
            $currentSession->idCatalog = $param['catalog'];
        }

        // Формирование запроса, в зависимости от к-ва параметров
        if (isset($param['brand']) && isset($param['catalog'])) {
            $query = array(
                'idBrand'   => $param['brand'],
                'idCatalog' => $param['catalog']
            );

            $brand    = $this->getEntityManager()->find(self::BRAND_ENTITY, $param['brand']);
            $category = $this->getEntityManager()->find(self::CATEGORY_ENTITY, $param['catalog']);
        } elseif (isset($param['brand'])) {
            $query = array(
                'idBrand'   => $param['brand']
            );

            $brand    = $this->getEntityManager()->find(self::BRAND_ENTITY, $param['brand']);
        } elseif (isset($param['catalog'])) {
            $query = array(
                'idCatalog' => $param['catalog']
            );

            $category = $this->getEntityManager()->find(self::CATEGORY_ENTITY, $param['catalog']);
        } else {
            $query = null;
        }

        if (!is_null($query)) {
            $result = $this->getEntityManager()
                ->getRepository(self::PRODUCT_ENTITY)->findBy($query);
        } else {
            $result = false;
        }

        $res = new ViewModel(array(
            'type'       => 'edit-product',
            'breadcrumbs'=> array('brand' => $brand, 'catalog' => $category),
            'result'     => $result
        ));

        if (!$externalCall) {
            $catalog = $this->forward()->dispatch('Catalog\Controller\Index', array('action' => 'index'));
            $res->addChild($catalog, 'catalog');
        }

        return $res;
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function addAction()
    {
        $form = $this->getForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $product = new ProductEntity();

            $form->setData($request->getPost());

            if ($form->isValid()) {
                // Вызов сервиса транслитерации
                $translit = $this->getServiceLocator()->get('translitService');

                $postData = $form->getData();

                $postData['price'] = (int)($postData['price'] * 100);
                $postData['idSupplier'] = $this->getEntityManager()->
                    find(self::STORE_ENTITY, $postData['idSupplier']);
                $postData['idCatalog'] = $this->getEntityManager()->
                    find(self::CATEGORY_ENTITY, $postData['idCatalog']);
                $postData['idBrand'] = $this->getEntityManager()->
                    find(self::BRAND_ENTITY, $postData['idBrand']);
                $postData['translit'] = $translit->getTranslit($postData['name']);

                // empty description fix
                if ($postData['description'] === 'empty') {
                    $postData['description'] = null;
                }

                $product->populate($postData);

                $this->getEntityManager()->persist($product);
                $this->getEntityManager()->flush();

                return $this->redirect()->toRoute('editproduct');
            }
        }

        $catalog = $this->modifyCatalogOptions();

        return new ViewModel(array(
            'form'     => $form,
            'catalog'  => $catalog,
            'supplier' => $this->setOptionItems('supplier'),
            'brand'    => $this->setOptionItems('brand')
        ));
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function editAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('editproduct');
        }

        $product = $this->getEntityManager()->find(self::PRODUCT_ENTITY, $id);

        $brandState    = $product->getIdBrand()->getId();
        $catalogState  = $product->getIdCatalog()->getId();
        $supplierState = $product->getIdSupplier()->getId();

        // Перевод в грн.
        $product->setPrice($product->getPrice() / 100);

        $form = $this->getForm();
        $form->setData($product->getArrayCopy());

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                // Вызов сервиса транслитерации
                $translit = $this->getServiceLocator()->get('translitService');

                $postData = $form->getData();

                $postData['idSupplier'] = $this->getEntityManager()->
                    find(self::STORE_ENTITY, $postData['idSupplier']);
                $postData['idBrand'] = $this->getEntityManager()->
                    find(self::BRAND_ENTITY, $postData['idBrand']);
                $postData['idCatalog'] = $this->getEntityManager()->
                    find(self::CATEGORY_ENTITY, $postData['idCatalog']);
                $postData['translit'] = $translit->getTranslit($postData['name']);

                // empty description fix
                if ($postData['description'] === 'empty') {
                    $postData['description'] = null;
                }

                // Обратно в коп.
                $postData['price'] = (int)($postData['price'] * 100);

                $postData['idStatus'] = $product->getIdStatus();
                $postData['indexed']  = $product->getIndexed();
                $postData['img']      = $product->getImg();
                $postData['qty']      = $product->getQty();

                $product->populate($postData);

                $this->getEntityManager()->persist($product);
                $this->getEntityManager()->flush();

                return $this->redirect()->toRoute('editproduct');
            }
        }

        $catalog = $this->modifyCatalogOptions();

        return new ViewModel(array(
            'form'          => $form,
            'catalog'       => $catalog,
            'supplier'      => $this->setOptionItems('supplier'),
            'brand'         => $this->setOptionItems('brand'),
            'supplierState' => $supplierState,
            'brandState'    => $brandState,
            'catalogState'  => $catalogState,
            'id'            => $id
        ));
    }

    /**
     * @return ViewModel
     */
    public function hideAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('editproduct');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {

            $hide = $request->getPost('hide', 'Нет');

            if ($hide == 'Да') {
                $id = (int)$request->getPost('id');
                $product = $this->getEntityManager()->find(self::PRODUCT_ENTITY, $id);

                if (is_null($product->getIdStatus()) || ($product->getIdStatus()->getId() === 3)) {
                    $product->setIdStatus($this->getEntityManager()->
                            find(self::STATUS_ENTITY, $id = 4));
                } else {
                    $product->setIdStatus($this->getEntityManager()->
                            find(self::STATUS_ENTITY, $id = 3));
                }

                $this->getEntityManager()->persist($product);
                $this->getEntityManager()->flush();
            }

            return $this->redirect()->toRoute('editproduct');
        }

        return new ViewModel(array(
            'id'       => $id,
            'product'  => $this->getEntityManager()->find(self::PRODUCT_ENTITY, $id)
        ));
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function imgAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('editproduct');
        }

        $form = new Form\ImgUploadForm('img-file-form');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            $form->setData($data);

            if ($form->isValid()) {
                $formData = $form->getData();

                $explode  = explode('/', $formData['file']['tmp_name']);
                $fileName = $explode[count($explode) - 1];

                $qb = $this->getEntityManager()->createQueryBuilder();

                // Название изображения | null
                $qs = $qb->select('p.img')
                    ->from(self::PRODUCT_ENTITY, 'p')
                    ->where('p.id = ?1')
                    ->setParameter(1, $id)
                    ->getQuery();
                $qs->execute();

                $qr = $qs->getResult();

                // Удалить старое изображение, если есть
                if (!is_null($qr[0]['img'])) {
                    $this->removeOldImg($qr[0]['img']);
                }

                $qu = $qb->update(self::PRODUCT_ENTITY, 'p')
                    ->set('p.img', '?1')
                    ->where('p.id = ?2')
                    ->setParameter(1, $fileName)
                    ->setParameter(2, $id)
                    ->getQuery();
                $qu->execute();

                return $this->redirect()->toRoute('editproduct');
            }
        }

        return new ViewModel(array(
            'form' => $form,
            'id'   => $id
        ));
    }

    /**
     * Removing old image
     *
     * @param $imageName
     *
     * @return bool
     */
    protected function removeOldImg($imageName)
    {
        $result = unlink('./public/img/product/' . $imageName);

        return $result;
    }

    /**
     * Add full name
     *
     * @require @function getFullNameCategory
     * @return array
     */
    protected function modifyCatalogOptions()
    {
        $catalog = $this->setOptionItems('catalog');

        for ($i = 0, $count = count($catalog); $i < $count; $i++) {
            $catalog[$i]['name'] = $this->getFullNameCategory($catalog[$i]['id']);

//            if (is_null($catalog[$i]['name'])) {
//                unset($catalog[$i]);
//            }

            $this->fullName = null;
        }

        return $catalog;
    }

    /**
     * Set options for select
     *
     * @param $type
     *
     * @return array
     */
    protected function setOptionItems($type)
    {
        $option_arr = array();

        if ($type == 'catalog') {
            $categories = $this->getEntityManager()
                ->getRepository(self::CATEGORY_ENTITY)->findAll();

            for ($i = 0, $category = count($categories); $i < $category; $i++) {
                $option_arr[$i]['id']   = $categories[$i]->getId();
                $option_arr[$i]['name'] = $categories[$i]->getName();
            }
        } elseif ($type == 'supplier') {
            $suppliers = $this->getEntityManager()
                ->getRepository(self::STORE_ENTITY)->findBy(array('idAttrib' => 3));

            for ($i = 0, $supplier = count($suppliers); $i < $supplier; $i++) {
                $option_arr[$i]['id']   = $suppliers[$i]->getId();
                $option_arr[$i]['name'] = $suppliers[$i]->getName();
            }
        } else {
            $brands = $this->getEntityManager()
                ->getRepository(self::BRAND_ENTITY)->findAll();

            for ($i = 0, $brand = count($brands); $i < $brand; $i++) {
                $option_arr[$i]['id']   = $brands[$i]->getId();
                $option_arr[$i]['name'] = $brands[$i]->getName();
            }
        }

        return $option_arr;
    }

    /**
     * Get full category name with parent category
     *
     * @param $id
     *
     * @return mixed
     */
    public function getFullNameCategory($id)
    {
        $category = $this->getEntityManager()->find(self::CATEGORY_ENTITY, $id);
        $fullName = $category->getName();

        if (null == $category->getIdParent()) {
            if (!$this->fullName) {
                $this->fullName = $fullName;
            }

            return $this->fullName;
        } else {
            $parent = $this->getEntityManager()->find(self::CATEGORY_ENTITY, $category->getIdParent());
            $parentName = $parent->getName();

            if ($this->fullName) {
                $this->fullName = $parentName . " :: " . $this->fullName;
            } else {
                $this->fullName = $parentName . " :: " . $fullName;
            }

            return $this->getFullNameCategory($parent->getId());
        }
    }

    /**
     * @return \Zend\Form\ElementInterface|\Zend\Form\FieldsetInterface|\Zend\Form\Form|\Zend\Form\FormInterface
     */
    protected function getForm()
    {
        $entity  = new Product();
        $builder = new AnnotationBuilder();
        $form    = $builder->createForm($entity);

        return $form;
    }

    /**
     * @return array|object
     */
    public function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }

        return $this->em;
    }
}