<?php
/**
 * Copyright (C) 2023 Pixel Développement
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Pixel\Module\ProductFiles\Entity\ProductFile;
use Doctrine\ORM\EntityManager;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Pixel_product_files extends Module implements WidgetInterface
{
    /**
     * Directory Separator
     *
     * @var string
     */
    const DS = DIRECTORY_SEPARATOR;

    /**
     * The file upload directory
     *
     * @var string
     */
    const FILE_BASE_DIR = _PS_ROOT_DIR_ . self::DS . 'img' . self::DS . 'product' . self::DS;

    /**
     * The file base URL
     *
     * @var string
     */
    const FILE_BASE_URL = _PS_BASE_URL_SSL_ . '/img/product/';

    /**
     * Upload file allowed extensions
     */
    const ALLOWED_EXTENSIONS = [
        'pdf', 'odt', 'doc', 'opt', 'docx', 'rtf',
        'csv', 'ods', 'xls', 'xlsx',
        'pptx', 'pptm', 'ppt', 'odp',
        'png', 'gif', 'svg', 'webp', 'jpeg', 'jpg', 'bmp', 'avif', 'apng', 'ico', 'tiff',
        'avi', 'mp4', 'm4v',
        'mp3', 'ogg', 'flac', 'wav', 'm4a', 'wma', 'aac',
        'zip', 'rar', 'gz', 'tar', 'bz2', 'xz', '7z'
    ];

    /**
     * File types
     */
    const FILE_TYPES = [
        'document' => ['pdf', 'odt', 'doc', 'opt', 'docx', 'rtf'],
        'table' => ['csv', 'ods', 'xls', 'xlsx'],
        'presentation' => ['pptx', 'pptm', 'ppt', 'odp'],
        'image' => ['png', 'gif', 'svg', 'webp', 'jpeg', 'jpg', 'bmp', 'avif', 'apng', 'ico', 'tiff'],
        'video' => ['avi', 'mp4', 'm4v'],
        'audio' => ['mp3', 'ogg', 'flac', 'wav', 'm4a', 'wma', 'aac'],
        'archive' => ['zip', 'rar', 'gz', 'tar', 'bz2', 'xz', '7z'],
    ];

    protected $templateFile;

    /**
     * Module's constructor.
     */
    public function __construct()
    {
        $this->name = 'pixel_product_files';
        $this->version = '1.3.1';
        $this->author = 'Pixel Open';
        $this->tab = 'content_management';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Product Files', [], 'Modules.Pixelproductfiles.Admin');
        $this->description = $this->trans('An easy way to attach files and documents to the product (multi-languages, multi-shops).', [], 'Modules.Pixelproductfiles.Admin');

        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => _PS_VERSION_,
        ];

        $this->templateFile = 'module:pixel_product_files/views/templates/widget/files.tpl';
    }

    /**
     * Use the new translation system
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Install module and register hooks.
     *
     * @return bool
     */
    public function install(): bool
    {
        return parent::install() &&
            $this->createTable() &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('displayAdminEndContent') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionAdminProductsControllerSaveBefore') &&
            $this->registerHook('actionBeforeUpdateProductFormHandler') &&
            $this->registerHook('actionFrontControllerSetMedia');
    }

    /**
     * Create tables
     */
    protected function createTable(): bool
    {
        try {
            Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_file` (
                    `id` INT(10) AUTO_INCREMENT NOT NULL,
                    `id_product` INT(10) unsigned NOT NULL,
                    `id_shop` INT(10) unsigned NULL DEFAULT NULL,
                    `id_lang` INT(10) unsigned NULL DEFAULT NULL,
                    `file` VARCHAR(255) NULL DEFAULT NULL,
                    `title` VARCHAR(255) NULL DEFAULT NULL,
                    `description` TEXT NULL DEFAULT NULL,
                    `position` INT(10) NOT NULL DEFAULT 0,
                    PRIMARY KEY(`id`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
            ');
        } catch (Exception $exception) {
            $this->_errors[] = $exception->getMessage();
            return false;
        }

        return true;
    }

    /************/
    /** WIDGET **/
    /************/

    /**
     * @param string $hookName
     * @param array $configuration
     *
     * @return string
     * @throws ContainerNotFoundException
     * @throws Exception
     */
    public function renderWidget($hookName, array $configuration): string
    {
        $idProduct = $configuration['id_product'] ?? Tools::getValue('id_product');
        if (!$idProduct) {
            return '';
        }
        $configuration['id_product'] = $idProduct;

        $keys = [$this->name, $this->context->shop->id, $this->context->language->id, md5(serialize($configuration))];
        $cacheId = join('_', $keys);

        $template = $configuration['template'] ?? $this->templateFile;

        if (!$this->isCached($template, $cacheId)) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($template, $cacheId);
    }

    /**
     * @param string $hookName
     * @param mixed[] $configuration
     *
     * @return Object[]
     * @throws ContainerNotFoundException
     */
    public function getWidgetVariables($hookName, array $configuration): array
    {
        $idProduct = $configuration['id_product'] ?? Tools::getValue('id_product');
        if (!$idProduct) {
            return [];
        }

        $idLang = $configuration['id_lang'] ?? $this->context->language->id;
        $idShop = $configuration['id_shop'] ?? $this->context->shop->id;

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $repository = $entityManager->getRepository(ProductFile::class);
        $files = $repository->findBy(
            [
                'idProduct' => (int)$idProduct,
                'idLang'    => [(int)$idLang, null, 0],
                'idShop'    => [(int)$idShop, null, 0],
            ],
            ['position' => 'asc']
        );

        return [
            'files' => $files,
            'icons' => $this->getIcons(),
            'path' => [
                'icons' => $configuration['icons_path'] ?? _PS_BASE_URL_SSL_ . $this->_path . 'views/icons/',
                'docs'  => self::FILE_BASE_URL,
            ],
        ];
    }

    /***********/
    /** HOOKS **/
    /***********/

    /**
     * Add CSS
     *
     * @return void
     */
    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/widget/product-files.css');
    }

    /**
     * Add product files form
     *
     * @param array $params
     * @return string
     * @throws ContainerNotFoundException
     */
    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $productId = $params['id_product'];

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $repository = $entityManager->getRepository(ProductFile::class);
        $files = $repository->findBy(
            [
                'idProduct' => $productId,
                'idShop'    => [(int)Context::getContext()->shop->id, null],
            ],
            ['position' => 'asc']
        );

        $available = Language::getLanguages();
        $languages = [];
        foreach ($available as $language) {
            $languages[$language['id_lang']] = $language['iso_code'];
        }

        $available = Shop::getShops();
        $shops = [];
        foreach ($available as $shop) {
            $shops[$shop['id_shop']] = $shop['name'];
        }

        $router = SymfonyContainer::getInstance()->get('router');

        return $this->get('twig')->render('@Modules/pixel_product_files/views/templates/admin/files.html.twig', [
            'files'              => $files,
            'file_base_url'      => self::FILE_BASE_URL,
            'languages'          => $languages,
            'shops'              => $shops,
            'id_product'         => $productId,
            'id_shop'            => (int)Context::getContext()->shop->id,
            'img_ext'            => self::FILE_TYPES['image'],
            'delete_url'         => $router->generate('product_files_delete_product_file'),
            'is_multi_languages' => count(Language::getLanguages()) > 1,
            'is_multi_shops'     => Shop::getTotalShops() > 1,
        ]);
    }

    /**
     * Add file popup HTML
     *
     * @param array $params
     *
     * @return string
     * @throws Exception
     */
    public function hookDisplayAdminEndContent(array $params): string
    {
        $router = SymfonyContainer::getInstance()->get('router');

        return $this->get('twig')->render('@Modules/pixel_product_files/views/templates/admin/select.html.twig', [
            'postUrl'            => $router->generate('product_files_save_product_file'),
            'fileBaseUrl'        => self::FILE_BASE_URL,
            'is_multi_languages' => count(Language::getLanguages()) > 1,
            'is_multi_shops'     => Shop::getTotalShops() > 1,
        ]);
    }

    /**
     * Save product file fields on product save
     * Prestashop >= 8.1
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function hookActionBeforeUpdateProductFormHandler(array $params): void
    {
        $request = $params['request'] ?? null;
        if ($request) {
            $this->saveProductFileData($request->get('file') ?? []);
        }
    }

    /**
     * Save product file fields on product save
     * Prestashop < 8.1
     *
     * @deprecated
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function hookActionAdminProductsControllerSaveBefore(array $params): void
    {
        $this->saveProductFileData($_REQUEST['file'] ?? []);
    }

    /**
     * Add CSS in the admin
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader(): void
    {
        if ($this->context->controller->controller_name === 'AdminProducts') {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin/product-files.css');
        }
    }

    /**
     * @param $files
     * @return void
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function saveProductFileData($files): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $repository = $entityManager->getRepository(ProductFile::class);

        foreach ($files as $id => $fields) {
            /** @var ProductFile $productFile */
            $productFile = $repository->findOneBy(['id' => $id]);
            if (!$productFile) {
                continue;
            }
            foreach ($fields as $field => $value) {
                switch ($field) {
                    case 'title':
                        $productFile->setTitle((string)$value);
                        break;
                    case 'description':
                        $productFile->setDescription((string)$value);
                        break;
                    case 'position':
                        $productFile->setPosition((int)$value);
                        break;
                }
            }
            $entityManager->persist($productFile);
            $entityManager->flush();
        }
    }

    /**************/
    /** USEFULLY **/
    /**************/

    protected function getIcons(): array
    {
        $icons = [];
        foreach (self::FILE_TYPES as $type => $extensions) {
            foreach ($extensions as $extension) {
                $icons[$extension] = $type . '.png';
            }
        }

        return $icons;
    }
}
